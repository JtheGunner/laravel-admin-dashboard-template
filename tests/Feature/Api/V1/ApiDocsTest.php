<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use Tests\TestCase;

class ApiDocsTest extends TestCase {
    public function test_committed_openapi_document_describes_the_v1_endpoints(): void {
        $document = json_decode((string) file_get_contents(base_path('docs/openapi.json')), true);

        $this->assertIsArray($document);
        $this->assertArrayHasKey('paths', $document);

        foreach (['/v1/users', '/v1/customers', '/v1/products', '/v1/categories'] as $path) {
            $this->assertArrayHasKey($path, $document['paths']);
        }
    }
}
