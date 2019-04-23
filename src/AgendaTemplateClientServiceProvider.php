<?php
namespace SoapBox\AgendaTemplateClient;

use Illuminate\Support\ServiceProvider;

class AgendaTemmplateClientServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'../config/agenda-template-client.php' => config_path('agenda-template-client.php'),
        ]);
    }
}
