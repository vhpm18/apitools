<?php

declare(strict_types=1);

namespace App\Http\Responses;

use App\Http\Factories\HeaderFactory;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use JustSteveKing\Tools\Http\Enums\Status;

final readonly class ExpandedResponse implements Responsable
{
    /**
     * @param string $message
     * @param array<string,mixed> $data
     * @param Status $status
     */
    public function __construct(
        private string $message,
        private array  $data,
        private Status $status = Status::OK,
    ) {}

    /**
     * @param $request
     * @return JsonResponse
     */
    public function toResponse($request): JsonResponse
    {
        return new JsonResponse(
            data: [
                'message' => $this->message,
                ...$this->data,
            ],
            status: $this->status->value,
            headers: HeaderFactory::default(),
        );
    }
}
