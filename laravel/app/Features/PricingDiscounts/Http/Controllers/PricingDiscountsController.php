<?php

namespace App\Features\PricingDiscounts\Http\Controllers;

use App\Features\PricingDiscounts\Actions\BuildPricingQuote;
use App\Features\PricingDiscounts\Actions\CreatePricingQuote;
use App\Features\PricingDiscounts\Catalog\PricingCatalog;
use App\Features\PricingDiscounts\Exceptions\PricingSelectionUnavailable;
use App\Features\PricingDiscounts\Http\Requests\QuoteCartRequest;
use App\Features\PricingDiscounts\Models\PricingPromotion;
use App\Features\PricingDiscounts\Models\PricingQuote;
use App\Features\PricingDiscounts\ValueObjects\PriceQuote;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PricingDiscountsController extends Controller
{
    public function index(BuildPricingQuote $build, PricingCatalog $catalog): View
    {
        $quoteInputs = session('pricing_quote_inputs');
        $products = $catalog->products();
        $rules = $catalog->promotions();
        $defaultQuantities = $products->mapWithKeys(fn ($product, int $index): array => [$product->sku => $index === 0 ? 2 : 1])->all();
        $defaultSelectedRules = $rules->filter(fn ($rule): bool => $rule->stackable)->pluck('code')->values()->all();
        $quantities = is_array($quoteInputs['quantities'] ?? null)
            ? $quoteInputs['quantities']
            : $defaultQuantities;
        $selectedRules = is_array($quoteInputs['rules'] ?? null)
            ? $quoteInputs['rules']
            : $defaultSelectedRules;
        try {
            $quote = $this->quoteFromSession($build, $quantities, $selectedRules);
        } catch (PricingSelectionUnavailable) {
            session()->forget(['pricing_quote_inputs', 'pricing_quote_id']);
            $quantities = $defaultQuantities;
            $selectedRules = $defaultSelectedRules;
            $quote = $build->handle($quantities, $selectedRules)->quote;
        }
        $recentQuotes = $this->recentQuotes();
        $adminPromotions = auth()->user()?->can('manage-catalog')
            ? PricingPromotion::query()->latest('id')->get()
            : new Collection;

        return view('labs.pricing-discounts.index', compact('quote', 'rules', 'quantities', 'selectedRules', 'recentQuotes', 'adminPromotions'));
    }

    public function quote(QuoteCartRequest $request, CreatePricingQuote $create): RedirectResponse
    {
        $quantities = $request->quantities();
        $selectedRules = $request->selectedRuleCodes();
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        try {
            $quote = $create->handle($actor, $quantities, $selectedRules);
        } catch (PricingSelectionUnavailable) {
            return back()
                ->withInput()
                ->withErrors(['pricing' => __('pricing_discounts.errors.selection_unavailable')]);
        }

        return to_route('labs.pricing-discounts.index')->with('pricing_quote_inputs', [
            'quantities' => $quantities,
            'rules' => $selectedRules,
        ])->with('pricing_quote_id', $quote->id);
    }

    public function show(int $quote): View
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User, 401);

        $pricingQuote = PricingQuote::query()
            ->whereKey($quote)
            ->where('created_by', $actor->id)
            ->with(['lines', 'discounts'])
            ->firstOrFail();

        return view('labs.pricing-discounts.show', ['pricingQuote' => $pricingQuote]);
    }

    /**
     * @param  array<string, int>  $quantities
     * @param  list<string>  $selectedRules
     */
    private function quoteFromSession(BuildPricingQuote $build, array $quantities, array $selectedRules): PriceQuote
    {
        $quoteId = session('pricing_quote_id');
        $actor = auth()->user();
        if ($quoteId !== null && $actor instanceof User) {
            $savedQuote = PricingQuote::query()
                ->where('created_by', $actor->id)
                ->with(['lines', 'discounts'])
                ->find($quoteId);

            if ($savedQuote !== null) {
                return $savedQuote->toPriceQuote();
            }
        }

        return $build->handle($quantities, $selectedRules)->quote;
    }

    /**
     * @return Collection<int, PricingQuote>
     */
    private function recentQuotes(): Collection
    {
        $actor = auth()->user();
        if (! $actor instanceof User) {
            return new Collection;
        }

        return PricingQuote::query()
            ->where('created_by', $actor->id)
            ->with(['lines', 'discounts'])
            ->latest()
            ->limit(5)
            ->get();
    }
}
