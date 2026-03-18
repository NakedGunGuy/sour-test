<?php

declare(strict_types=1);

namespace Sauerkraut\Http;

class HttpClient
{
    private const int DEFAULT_TIMEOUT = 30;
    private const int MAX_REDIRECTS = 5;

    private array $defaultHeaders = [];
    private int $timeout;

    public function __construct(int $timeout = self::DEFAULT_TIMEOUT)
    {
        $this->timeout = $timeout;
    }

    public function withHeaders(array $headers): static
    {
        $clone = clone $this;
        $clone->defaultHeaders = array_merge($this->defaultHeaders, $headers);

        return $clone;
    }

    public function withTimeout(int $seconds): static
    {
        $clone = clone $this;
        $clone->timeout = $seconds;

        return $clone;
    }

    public function get(string $url, array $query = []): HttpResponse
    {
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        return $this->send(new HttpRequest('GET', $url));
    }

    public function post(string $url, array $data = []): HttpResponse
    {
        return $this->send(new HttpRequest('POST', $url, json_encode($data), [
            'Content-Type' => 'application/json',
        ]));
    }

    public function put(string $url, array $data = []): HttpResponse
    {
        return $this->send(new HttpRequest('PUT', $url, json_encode($data), [
            'Content-Type' => 'application/json',
        ]));
    }

    public function patch(string $url, array $data = []): HttpResponse
    {
        return $this->send(new HttpRequest('PATCH', $url, json_encode($data), [
            'Content-Type' => 'application/json',
        ]));
    }

    public function delete(string $url): HttpResponse
    {
        return $this->send(new HttpRequest('DELETE', $url));
    }

    public function postForm(string $url, array $data): HttpResponse
    {
        return $this->send(new HttpRequest('POST', $url, http_build_query($data), [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]));
    }

    public function send(HttpRequest $request): HttpResponse
    {
        $curl = curl_init();
        $mergedHeaders = array_merge($this->defaultHeaders, $request->headers);

        curl_setopt_array($curl, [
            CURLOPT_URL => $request->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CUSTOMREQUEST => $request->method,
            CURLOPT_HTTPHEADER => $this->formatHeaders($mergedHeaders),
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => self::MAX_REDIRECTS,
        ]);

        if ($request->body !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $request->body);
        }

        $response = curl_exec($curl);

        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new \RuntimeException("HTTP request failed: {$error}");
        }

        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        curl_close($curl);

        $responseHeaders = $this->parseHeaders(substr($response, 0, $headerSize));
        $responseBody = substr($response, $headerSize);

        return new HttpResponse($statusCode, $responseBody, $responseHeaders);
    }

    /** @return string[] */
    private function formatHeaders(array $headers): array
    {
        $formatted = [];

        foreach ($headers as $name => $value) {
            $formatted[] = "{$name}: {$value}";
        }

        return $formatted;
    }

    /** @return array<string, string> */
    private function parseHeaders(string $headerString): array
    {
        $headers = [];

        foreach (explode("\r\n", $headerString) as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $line, 2);
            $headers[trim($name)] = trim($value);
        }

        return $headers;
    }
}
