<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\Http\Controllers;

use Hki\AssetRegister\Asset\Contracts\Services\AssetServiceInterface;
use Hki\AssetRegister\Asset\Factories\AssetDataFactory;
use Hki\AssetRegister\Asset\Factories\AssetRegistryFiltersFactory;
use Hki\AssetRegister\Asset\Http\Requests\AssetRegistryFilterRequest;
use Hki\AssetRegister\Asset\Http\Requests\CreateAssetFormRequest;
use Hki\AssetRegister\Asset\Http\Requests\EditAssetRequest;
use Hki\AssetRegister\Asset\Http\Requests\ShowAssetRequest;
use Hki\AssetRegister\Asset\Http\Requests\StoreAssetRequest;
use Hki\AssetRegister\Asset\Http\Requests\UpdateAssetRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class AssetController
{
    public function __construct(
        private readonly AssetServiceInterface $service,
        private readonly AssetDataFactory $assetDataFactory,
        private readonly AssetRegistryFiltersFactory $filtersFactory,
    ) {}

    public function registry(AssetRegistryFilterRequest $request): View
    {
        $pageData = $this->service->registryPage(
            $request->user(),
            $this->filtersFactory->fromArray($request->validated()),
        );

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
        return view('asset-register::asset-register.asset-show', $this->service->detailPage($request->user(), $assetId));
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
