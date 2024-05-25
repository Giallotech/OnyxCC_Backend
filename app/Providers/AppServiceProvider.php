<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class AppServiceProvider extends ServiceProvider {
  /**
   * Register any application services.
   */
  public function register(): void {
    //
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void {
    Validator::extend('streamable', function ($attribute, $value, $parameters, $validator) {
      return in_array($value->getClientOriginalExtension(), ['mp4', 'webm', 'ogg', 'm3u8']);
    });
  }
}
