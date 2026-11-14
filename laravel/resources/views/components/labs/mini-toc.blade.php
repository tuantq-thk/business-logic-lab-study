@props(['lab'])

@php($legacy = [
    'booking-overlap' => ['tradeoffs' => 'booking-overlap-tradeoffs', 'maturity' => 'booking-overlap-maturity'],
    'contextual-validation' => ['tradeoffs' => 'contextual-validation-tradeoffs'],
])
@php($groups = [
    ['label' => __('lab_learning.navigation.context_label'), 'items' => [
        ['label' => __('lab_learning.navigation.objectives'), 'target' => $lab.'-objectives'],
        ['label' => __('lab_learning.navigation.theory'), 'target' => $lab.'-theory'],
        ['label' => __('lab_learning.navigation.invariant'), 'target' => $lab.'-invariant'],
    ]],
    ['label' => __('lab_learning.navigation.code_label'), 'items' => [
        ['label' => __('lab_learning.navigation.architecture'), 'target' => $lab.'-architecture'],
        ['label' => __('lab_learning.navigation.data'), 'target' => $lab.'-data-contract'],
        ['label' => __('lab_learning.navigation.decisions'), 'target' => $lab.'-decision-table'],
        ['label' => __('lab_learning.navigation.flow'), 'target' => $lab.'-flow'],
        ['label' => __('lab_learning.navigation.examples'), 'target' => $lab.'-examples'],
    ]],
    ['label' => __('lab_learning.navigation.production_label'), 'items' => [
        ['label' => __('lab_learning.navigation.evidence'), 'target' => $lab.'-test-evidence'],
        ['label' => __('lab_learning.navigation.performance'), 'target' => $lab.'-performance'],
        ['label' => __('lab_learning.navigation.tradeoffs'), 'target' => $legacy[$lab]['tradeoffs'] ?? $lab.'-tradeoffs'],
        ['label' => __('lab_learning.navigation.threats'), 'target' => $legacy[$lab]['threats'] ?? $lab.'-threats'],
        ['label' => __('lab_learning.navigation.pitfalls'), 'target' => $lab.'-pitfalls'],
        ['label' => __('lab_learning.navigation.maturity'), 'target' => $legacy[$lab]['maturity'] ?? $lab.'-maturity'],
        ['label' => __('lab_learning.navigation.observability'), 'target' => $lab.'-observability'],
        ['label' => __('lab_learning.navigation.review'), 'target' => $legacy[$lab]['review'] ?? $lab.'-review'],
    ]],
    ['label' => __('lab_learning.navigation.practice_label'), 'items' => [
        ['label' => __('lab_learning.navigation.practice'), 'target' => $lab.'-practice'],
    ]],
])

<aside class="lab-mini-toc" aria-label="{{ __('lab_learning.navigation.aria_label') }}">
    <div class="lab-mini-toc__heading">{{ __('lab_learning.navigation.title') }}</div>
    <div class="lab-mini-toc__groups">
        @foreach ($groups as $group)
            <div class="lab-mini-toc__group">
                <p class="lab-mini-toc__label">{{ $group['label'] }}</p>
                <div class="lab-mini-toc__links">
                    @foreach ($group['items'] as $item)
                        <a href="#{{ $item['target'] }}" data-lab-toc-link>{{ $item['label'] }}</a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</aside>
