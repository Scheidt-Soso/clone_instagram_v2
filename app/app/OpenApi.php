<?php

namespace App;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Instagram Clone API",
    description: "API do projeto pessoal - clone do Instagram feito em Laravel"
)]
#[OA\Server(
    url: "http://localhost:8000/api",
    description: "Ambiente de desenvolvimento"
)]
#[OA\SecurityScheme(
    securityScheme: "sanctum",
    type: "http",
    scheme: "bearer",
    bearerFormat: "Sanctum Token"
)]
class OpenApi
{
    //
}