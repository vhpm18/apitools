<?php

declare(strict_types=1);

namespace App\Http\Responses\V1;


use App\Http\Factories\HeaderFactory;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\AbstractPaginator;
use JustSteveKing\Tools\Http\Enums\Status;

final readonly class PaginateResponse implements Responsable
{
    public function __construct(
        private AbstractPaginator $data,
        private Status $status = Status::OK
    ) {}

    public function toResponse($request): JsonResponse
    {
        return new JsonResponse(
            data: $this->data,
            headers: HeaderFactory::default(),
            status: $this->status->value,
        );
    }
}
