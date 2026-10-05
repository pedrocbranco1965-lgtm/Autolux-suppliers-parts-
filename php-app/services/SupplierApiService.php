<?php
class SupplierApiService
{
    public function __construct(private string $baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        if (!filter_var($this->baseUrl, FILTER_VALIDATE_URL)
            || !in_array(parse_url($this->baseUrl, PHP_URL_SCHEME), ['http', 'https'], true)) {
            throw new InvalidArgumentException('O endereco da API deve ser HTTP ou HTTPS.');
        }
    }

    public function getSuppliers(): array { return $this->request('GET', '/suppliers'); }
    public function getOrders(): array { return $this->request('GET', '/orders'); }
    public function getOrder(int $id): array { return $this->request('GET', '/orders/' . $this->validId($id)); }
    public function createOrder(array $data): array { return $this->request('POST', '/orders', $data); }
    public function updateOrder(int $id, array $data): array
    { return $this->request('PUT', '/orders/' . $this->validId($id), $data); }
    public function deleteOrder(int $id): array
    { return $this->request('DELETE', '/orders/' . $this->validId($id)); }

    private function validId(int $id): int
    {
        if ($id < 1 || $id > 2147483647) {
            throw new InvalidArgumentException('Identificador de encomenda invalido.');
        }
        return $id;
    }

    private function request(string $method, string $endpoint, ?array $data = null): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('Ative a extensao cURL na instalacao PHP utilizada pelo MAMP.', 503);
        }
        $curl = curl_init($this->baseUrl . $endpoint);
        if ($curl === false) {
            throw new RuntimeException('Nao foi possivel iniciar cURL.');
        }
        try {
            $headers = ['Accept: application/json'];
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT => 12,
                CURLOPT_FOLLOWLOCATION => false,
            ]);
            if ($data !== null) {
                $headers[] = 'Content-Type: application/json';
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data, JSON_THROW_ON_ERROR));
            }
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            $response = curl_exec($curl);
            if ($response === false) {
                error_log('AutoLux cURL: ' . curl_error($curl));
                throw new InvalidArgumentException('A API Node nao respondeu. Confirme npm start e o endereco configurado. Se estava a gravar, consulte a lista antes de repetir o pedido.');
            }
            $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
            try {
                $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $error) {
                throw new InvalidArgumentException('A API devolveu uma resposta que nao e JSON valido.');
            }
            if (!is_array($decoded) || !isset($decoded['success']) || !is_bool($decoded['success'])) {
                throw new InvalidArgumentException('A resposta da API nao tem o formato esperado.');
            }
            if ($status < 200 || $status >= 300 || $decoded['success'] !== true) {
                $message = $status < 500 && is_string($decoded['message'] ?? null)
                    ? $decoded['message'] : 'A API nao conseguiu concluir a operacao.';
                throw new InvalidArgumentException($message);
            }
            return $decoded;
        } finally {
            curl_close($curl);
        }
    }
}
