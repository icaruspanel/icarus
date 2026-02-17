<?php

use App\Boot;
use Illuminate\Foundation\Application;

return Application::configure(basePath: dirname(__DIR__))
                  ->withRouting(Boot\ConfigureRouting::make())
                  ->withMiddleware(Boot\ConfigureMiddleware::make())
                  ->withExceptions(Boot\ConfigureExceptions::make())
                  ->create()
                  ->dontMergeFrameworkConfiguration();
