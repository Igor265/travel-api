<?php

namespace App\Http\Controllers;

use App\Enums\TravelOrderStatus;
use App\Http\Requests\TravelOrder\ListTravelOrdersRequest;
use App\Http\Requests\TravelOrder\StoreTravelOrderRequest;
use App\Http\Requests\TravelOrder\UpdateTravelOrderStatusRequest;
use App\Http\Resources\TravelOrderResource;
use App\Models\TravelOrder;
use App\Notifications\TravelOrderStatusChanged;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TravelOrderController extends Controller
{
    public function store(StoreTravelOrderRequest $request): Response|JsonResponse
    {
        $this->authorize('create', TravelOrder::class);

        $order = $request->user()->travelOrders()->create(
            array_merge($request->validated(), ['status' => TravelOrderStatus::Requested])
        );

        return (new TravelOrderResource($order))
            ->response()
            ->setStatusCode(201);
    }

    public function index(ListTravelOrdersRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', TravelOrder::class);

        $orders = $request->user()
            ->travelOrders()
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->destination, fn ($q, $v) => $q->where('destination', 'like', "%{$v}%"))
            ->when($request->departure_from, fn ($q, $v) => $q->whereDate('departure_date', '>=', $v))
            ->when($request->departure_to, fn ($q, $v) => $q->whereDate('departure_date', '<=', $v))
            ->when($request->return_from, fn ($q, $v) => $q->whereDate('return_date', '>=', $v))
            ->when($request->return_to, fn ($q, $v) => $q->whereDate('return_date', '<=', $v))
            ->paginate($request->integer('per_page', 15));

        return TravelOrderResource::collection($orders);
    }

    public function show(TravelOrder $travelOrder): TravelOrderResource
    {
        $this->authorize('view', $travelOrder);

        return new TravelOrderResource($travelOrder);
    }

    public function updateStatus(UpdateTravelOrderStatusRequest $request, TravelOrder $travelOrder): TravelOrderResource|JsonResponse
    {
        $this->authorize('updateStatus', $travelOrder);

        $newStatus = TravelOrderStatus::from($request->status);

        if (! $travelOrder->status->canTransitionTo($newStatus)) {
            return response()->json([
                'message' => "Cannot transition from '{$travelOrder->status->value}' to '{$newStatus->value}'.",
            ], 422);
        }

        $travelOrder->loadMissing('user');

        $travelOrder->update(['status' => $newStatus]);

        $travelOrder->user->notify(new TravelOrderStatusChanged($travelOrder));

        return new TravelOrderResource($travelOrder);
    }
}
