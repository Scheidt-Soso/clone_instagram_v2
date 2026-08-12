<?php

namespace App;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Instagram Clone API',
    description: 'API do projeto pessoal - clone do Instagram feito em Laravel'
)]
#[OA\Server(
    url: 'http://localhost:8000/api',
    description: 'Ambiente de desenvolvimento'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum Token'
)]
#[OA\Get(
    path: '/me',
    summary: 'Dados do usuário autenticado',
    tags: ['Autenticação'],
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Usuário autenticado'),
        new OA\Response(response: 401, description: 'Não autenticado'),
    ]
)]
class OpenApi
{
    //
}
