<?php

declare(strict_types=1);

namespace App\Http\Responses\V1;

use App\Http\Factories\HeaderFactory;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use JustSteveKing\Tools\Http\Enums\Status;
use Symfony\Component\HttpFoundation\Response;

final readonly class CollectionResponse implements Responsable
{
    public function __construct(
        private ResourceCollection $data,
        private Status $status = Status::OK
    ) {}

    public function toResponse($request): Response
    {
        return new JsonResponse(
            data: $this->data,
            headers: HeaderFactory::default(),
            status: $this->status->value,
        );
    }
}
