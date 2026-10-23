<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\Http\Controllers;

use Hki\AssetRegister\Asset\Contracts\Services\AssetServiceInterface;
use Hki\AssetRegister\Asset\Factories\AssetLocationDataFactory;
use Hki\AssetRegister\Asset\Factories\AssetTypeDataFactory;
use Hki\AssetRegister\Asset\Http\Requests\AssetIndexRequest;
use Hki\AssetRegister\Asset\Http\Requests\AssetMineRequest;
use Hki\AssetRegister\Asset\Http\Requests\AssetSettingsRequest;
use Hki\AssetRegister\Asset\Http\Requests\DeleteAssetLocationRequest;
use Hki\AssetRegister\Asset\Http\Requests\DeleteAssetTypeRequest;
use Hki\AssetRegister\Asset\Http\Requests\StoreAssetLocationRequest;
use Hki\AssetRegister\Asset\Http\Requests\StoreAssetTypeRequest;
use Hki\AssetRegister\Asset\Http\Requests\UpdateAssetLocationRequest;
use Hki\AssetRegister\Asset\Http\Requests\UpdateAssetTypeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class AssetRegisterController
{
    public function __construct(
        private readonly AssetServiceInterface $service,
        private readonly AssetTypeDataFactory $typeDataFactory,
        private readonly AssetLocationDataFactory $locationDataFactory,
    ) {}

    public function index(AssetIndexRequest $request): RedirectResponse
    {
        return redirect()->route($this->service->landingRoute($request->user()));
    }

    public function mine(AssetMineRequest $request): View
    {
        return view('asset-register::asset-register.page', $this->service->minePage($request->user()));
    }

    public function settings(AssetSettingsRequest $request): View
    {
        return view('asset-register::asset-register.settings', $this->service->settingsPage($request->user()));
    }

    public function storeType(StoreAssetTypeRequest $request): RedirectResponse
    {
        $this->service->createType(
            $request->user(),
            $this->typeDataFactory->fromArray($request->validated()),
        );

        return redirect()->route('asset-register.settings')->with('success', __('asset-register::asset-register.messages.type_created'));
    }

    public function updateType(UpdateAssetTypeRequest $request, int $typeId): RedirectResponse
    {
        $this->service->updateType(
            $request->user(),
            $typeId,
            $this->typeDataFactory->fromArray($request->validated()),
        );

        return redirect()->route('asset-register.settings')->with('success', __('asset-register::asset-register.messages.type_updated'));
    }

    public function deleteType(DeleteAssetTypeRequest $request, int $typeId): RedirectResponse
    {
        $this->service->deleteType($request->user(), $typeId);

        return redirect()->route('asset-register.settings')->with('success', __('asset-register::asset-register.messages.type_deleted'));
    }

    public function storeLocation(StoreAssetLocationRequest $request): RedirectResponse
    {
        $this->service->createLocation(
            $request->user(),
            $this->locationDataFactory->fromArray($request->validated()),
        );

        return redirect()->route('asset-register.settings')->with('success', __('asset-register::asset-register.messages.location_created'));
    }

    public function updateLocation(UpdateAssetLocationRequest $request, int $locationId): RedirectResponse
    {
        $this->service->updateLocation(
            $request->user(),
            $locationId,
            $this->locationDataFactory->fromArray($request->validated()),
        );

        return redirect()->route('asset-register.settings')->with('success', __('asset-register::asset-register.messages.location_updated'));
    }

    public function deleteLocation(DeleteAssetLocationRequest $request, int $locationId): RedirectResponse
    {
        $this->service->deleteLocation($request->user(), $locationId);

        return redirect()->route('asset-register.settings')->with('success', __('asset-register::asset-register.messages.location_deleted'));
    }
}
