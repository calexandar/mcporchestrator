<?php

use App\Mcp\Http\Middleware\AuthenticateMcpRequest;
use App\Mcp\Servers\LabServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/api/mcp', LabServer::class)
    ->middleware(AuthenticateMcpRequest::class);
