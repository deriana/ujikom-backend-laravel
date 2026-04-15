<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePointItemRequest;
use App\Http\Requests\UpdatePointItemRequest;
use App\Http\Resources\PointItemResource;
use App\Http\Resources\PointItemDetailResource;
use App\Http\Resources\PointInventoryResource;
use App\Http\Resources\PointMutationResource;
use App\Models\PointItem;
use App\Models\EmployeeInventories;
use App\Services\PointItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\JsonResponse;

class PointItemController extends Controller
{
    protected PointItemService $pointItemService;

    /**
     * Membuat instance PointItemController baru.
     *
     * @param PointItemService $pointItemService
     */
    public function __construct(PointItemService $pointItemService)
    {
        $this->pointItemService = $pointItemService;
    }

    /**
     * Menampilkan daftar semua item poin.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function index(): JsonResponse
    {
        // $this->authorize('viewAny', PointItem::class);

        $items = $this->pointItemService->index();

        return $this->successResponse(
            PointItemResource::collection($items),
            'Point Items fetched successfully'
        );
    }

    /**
     * Menampilkan detail item poin tertentu.
     *
     * @param PointItem $point_item
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function show(PointItem $point_item): JsonResponse
    {
        // $this->authorize('view', $point_item);

        $item = $this->pointItemService->show($point_item);

        return $this->successResponse(
            new PointItemDetailResource($item),
            'Point Item details fetched successfully'
        );
    }

    /**
     * Menyimpan item poin baru ke database.
     *
     * @param CreatePointItemRequest $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function store(CreatePointItemRequest $request): JsonResponse
    {
        $this->authorize('create', PointItem::class);

        $item = $this->pointItemService->store($request->validated());

        return $this->successResponse(
            new PointItemResource($item),
            'Point Item created successfully',
            201
        );
    }

    /**
     * Memperbarui data item poin yang sudah ada.
     *
     * @param UpdatePointItemRequest $request
     * @param PointItem $point_item
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdatePointItemRequest $request, PointItem $point_item): JsonResponse
    {
        $this->authorize('update', $point_item);

        $updated = $this->pointItemService->update($point_item, $request->validated());

        return $this->successResponse(
            new PointItemResource($updated),
            'Point Item updated successfully'
        );
    }

    /**
     * Menghapus item poin dari database.
     *
     * @param PointItem $point_item
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function destroy(PointItem $point_item): JsonResponse
    {
        $this->authorize('delete', $point_item);

        $this->pointItemService->delete($point_item);

        return $this->successResponse(null, 'Point Item deleted successfully');
    }

    /**
     * Menyesuaikan stok item secara manual.
     */
    public function adjustStock(Request $request, PointItem $point_item): JsonResponse
    {
        $this->authorize('update', $point_item);

        $request->validate(['adjustment' => 'required|integer']);

        $updated = $this->pointItemService->adjustStock($point_item, $request->adjustment);

        return $this->successResponse(new PointItemResource($updated), 'Stock adjusted successfully');
    }

    /**
     * Mengubah status aktif/non-aktif item poin.
     *
     * @param PointItem $point_item
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function toggleStatus(PointItem $point_item): JsonResponse
    {
        $this->authorize('update', $point_item);

        $this->pointItemService->toggleStatus($point_item);

        return $this->successResponse(null, 'Point Item status updated successfully');
    }

    /**
     * Menangani proses penukaran poin (redeem) oleh karyawan.
     *
     * @param Request $request
     * @param PointItem $point_item
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function redeem(Request $request, PointItem $point_item): JsonResponse
    {
        $request->validate([
            'quantity' => 'nullable|integer|min:1',
        ]);

        $transaction = $this->pointItemService->redeem([
            'employee' => Auth::user()->employee,
            'point_item' => $point_item,
            'quantity' => $request->quantity ?? 1,
        ]);

        return $this->successResponse($transaction, 'Item redeemed successfully');
    }

    /**
     * Mengambil daftar inventaris item milik karyawan yang sedang login.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function inventories(): JsonResponse
    {
        $employee = Auth::user()->employee;

        $inventories = $this->pointItemService->getInventories($employee);

        return $this->successResponse(
            PointInventoryResource::collection($inventories),
            'Employee inventories fetched successfully'
        );
    }

    /**
     * Menggunakan item dari inventaris (khusus item non-otomatis).
     *
     * @param EmployeeInventories $inventory
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function useItem(EmployeeInventories $point_item): JsonResponse
    {

        if ($point_item->employee_id !== Auth::user()->employee->id) {
            return $this->errorResponse('Unauthorized access to this item.', 403);
        }

        $updated = $this->pointItemService->useItem($point_item);

        return $this->successResponse(
            new PointInventoryResource($updated),
            'Item used successfully'
        );
    }

    /**
     * Mengambil saldo poin saat ini untuk karyawan yang sedang login.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function wallet(): JsonResponse
    {
        $wallet = $this->pointItemService->getWallet(Auth::user()->employee);

        return $this->successResponse($wallet, 'Wallet balance fetched successfully');
    }

    /**
     * Mengambil riwayat mutasi poin (masuk & keluar) milik karyawan yang sedang login.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function mutations(): JsonResponse
    {
        $mutations = $this->pointItemService->getMutations(Auth::user()->employee);

        return $this->successResponse(
            PointMutationResource::collection($mutations),
            'Point mutations fetched successfully'
        );
    }
}
