<?php

namespace App\Features\BusinessCalendarSlots\Http\Controllers;

use App\Features\BusinessCalendarSlots\Actions\GenerateRoomSlots;
use App\Features\BusinessCalendarSlots\Http\Requests\AvailabilityRequest;
use App\Features\BusinessCalendarSlots\Models\Room;
use App\Features\BusinessCalendarSlots\Services\NextAvailableDateFinder;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\View\View;
use InvalidArgumentException;

class BusinessCalendarSlotsController extends Controller
{
    public function index(AvailabilityRequest $request, GenerateRoomSlots $generate, NextAvailableDateFinder $dateFinder): View
    {
        $rooms = Room::query()->where('active', true)->orderBy('code')->get();
        $data = $request->validated();
        $room = $rooms->firstWhere('id', (int) ($data['room_id'] ?? 0)) ?? $rooms->first();
        $date = $data['date'] ?? ($room === null ? CarbonImmutable::now()->toDateString() : $dateFinder->find($room));
        $duration = (int) ($data['duration'] ?? 60);
        $step = (int) ($data['step'] ?? 30);
        $generationError = null;
        $slots = [];

        if ($room !== null) {
            try {
                $slots = $generate->handle($room, $date, $duration, $step, CarbonImmutable::now());
            } catch (InvalidArgumentException $exception) {
                report($exception);
                $generationError = str_starts_with($exception->getMessage(), 'business_calendar_slots.')
                    ? __($exception->getMessage())
                    : __('business_calendar_slots.errors.generation_failed');
            }
        }

        return view('labs.business-calendar-slots.index', compact('rooms', 'room', 'date', 'duration', 'step', 'slots', 'generationError'));
    }
}
