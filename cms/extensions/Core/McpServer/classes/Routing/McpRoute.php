<?php

namespace Frootbox\Ext\Core\McpServer\Routing;

/**
 * Reserves the endpoint until authentication and the MCP transport are implemented.
 */
class McpRoute extends \Frootbox\Routing\AbstractRoute
{
    protected function getMatchingRegex(): string
    {
        return '#^mcp/?(?:\?.*)?$#D';
    }

    public function performRouting(): void
    {
        // Fail closed for every HTTP method. No CMS operations are exposed yet.
        http_response_code(503);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') {
            echo json_encode([
                'error' => 'mcp_not_configured',
                'message' => 'The MCP server is not configured yet.',
            ], JSON_UNESCAPED_SLASHES);
        }

        exit;
    }
}
