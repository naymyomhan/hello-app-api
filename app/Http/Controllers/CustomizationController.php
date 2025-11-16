<?php

namespace App\Http\Controllers;

use App\Http\Resources\BannerResource;
use App\Http\Resources\SettingApiResource;
use App\Models\Banner;
use App\Models\Setting;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class CustomizationController extends Controller
{
    use ResponseTrait;

    public function serverStatus(){
        $setting = Setting::first();
        return $this->success('Get Server Status successfully', new SettingApiResource($setting));
    }

    public function getBanners()
    {
        $banners = Banner::all();
        return $this->success('Get Banners successfully', BannerResource::collection($banners));
    }
}
