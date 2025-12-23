<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RequestContext
{
    public function __construct(protected Request $request) {}

    public function requestId(): string
    {
        $id = (string) $this->request->headers->get('X-Request-Id');

        if(!$id) {
            $id = (string) $this->request->attributes->get('request_id');
        }

        if(!$id) {
            $id = (string) Str::uuid();
            $this->request->attributes->set('request_id', $id);
        }

        return $id;
    }

    public function ip(): ?string
    {
        return $this->request->ip();
    }

    public function userAgent(): ?string
    {
        return $this->request->userAgent();
    }

    public function routeName(): ?string
    {
        return optional($this->request->route())->getName();
    }

    public function method(): ?string
    {
        return $this->request->method();
    }

    public function url(): ?string
    {
        return $this->request->fullUrl();
    }
}
