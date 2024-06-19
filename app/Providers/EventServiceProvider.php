<?php

namespace App\Providers;

use App\Events\OurExampleEvent;
use App\listeners\OurExampleListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array
     */

        protected $listen = [
            OurExampleEvent::class => [
                OurExampleListener::class
            ],
            Registered::class => [
                SendEmailVerificationNotification::class,
            ],
        'App\Events\SomeEvent' => [
            'App\Listeners\SomeListener',
        ],
        // Add your events and listeners here
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
