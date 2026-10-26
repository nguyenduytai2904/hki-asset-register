<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\Http\Controllers;

use App\Contracts\Services\NotificationServices\NotificationAutoReadServiceInterface;
use App\Http\Controllers\Concerns\RendersDataTableRegion;
use App\Http\Controllers\Web\BaseWebController;
use Hki\AssetRegister\Asset\Contracts\Services\AssetServiceInterface;
use Hki\AssetRegister\Asset\Factories\AssetDataFactory;
use Hki\AssetRegister\Asset\Factories\AssetRegistryFiltersFactory;
use Hki\AssetRegister\Asset\Http\Requests\AssetRegistryFilterRequest;
use Hki\AssetRegister\Asset\Http\Requests\CreateAssetFormRequest;
use Hki\AssetRegister\Asset\Http\Requests\EditAssetRequest;
use Hki\AssetRegister\Asset\Http\Requests\ShowAssetRequest;
use Hki\AssetRegister\Asset\Http\Requests\StoreAssetRequest;
use Hki\AssetRegister\Asset\Http\Requests\UpdateAssetRequest;
use Hki\AssetRegister\Asset\Models\Asset;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class AssetController extends BaseWebController
{
    use RendersDataTableRegion;

    public function __construct(
        private readonly AssetServiceInterface $service,
        private readonly AssetDataFactory $assetDataFactory,
        private readonly AssetRegistryFiltersFactory $filtersFactory,
        private readonly NotificationAutoReadServiceInterface $notificationAutoRead,
    ) {}

    public function registry(AssetRegistryFilterRequest $request): View
    {
        [$sortBy, $sortDirection] = $this->tableSort($request, ['asset', 'status', 'warranty']);
        $filters = $request->validated();
        $filters['q'] = $this->tableSearchTerm($request);
        $filters['sort'] = $sortBy ?? 'asset';
        $filters['direction'] = $sortDirection ?? 'asc';
        $filters['sort_by'] = $sortBy;
        $filters['per_page'] = $this->tablePerPage($request);

        $pageData = $this->service->registryPage(
            $request->user(),
            $this->filtersFactory->fromArray($filters),
        );

        if ($this->wantsTableRegion($request)) {
            return view('asset-register::asset-register.partials.registry-table-region', $pageData);
        }

        return view('asset-register::asset-register.page', $pageData);
    }

    public function create(CreateAssetFormRequest $request): View
    {
        return view('asset-register::asset-register.asset-form', $this->service->createForm($request->user()));
    }

    public function store(StoreAssetRequest $request): RedirectResponse
    {
        $asset = $this->service->create(
            $request->user(),
            $this->assetDataFactory->fromArray($request->validated()),
        );

        return redirect()
            ->route('asset-register.assets.show', $asset->id)
            ->with('success', __('asset-register::asset-register.messages.asset_created'));
    }

    public function show(ShowAssetRequest $request, int $assetId): View
    {
        $pageData = $this->service->detailPage($request->user(), $assetId);
        $this->notificationAutoRead->markSubjectsRead(
            (int) $request->user()->id,
            Asset::NOTIFICATION_SUBJECT_TYPE,
            [$assetId],
        );

        return view('asset-register::asset-register.asset-show', $pageData);
    }

    public function edit(EditAssetRequest $request, int $assetId): View
    {
        return view('asset-register::asset-register.asset-form', $this->service->editForm($request->user(), $assetId));
    }

    public function update(UpdateAssetRequest $request, int $assetId): RedirectResponse
    {
        $asset = $this->service->update(
            $request->user(),
            $assetId,
            $this->assetDataFactory->fromArray($request->validated()),
        );

        return redirect()
            ->route('asset-register.assets.show', $asset->id)
            ->with('success', __('asset-register::asset-register.messages.asset_updated'));
    }
}
