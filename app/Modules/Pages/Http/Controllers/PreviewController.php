<?php

namespace App\Modules\Pages\Http\Controllers;

use App\Modules\Pages\Services\MarkdownRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Live preview of the Markdown editor. Rendered by the same renderer as the
 * page itself (HTML escaped, unsafe links removed); no data is read or stored.
 */
class PreviewController
{
    public function __invoke(Request $request, MarkdownRenderer $markdown): JsonResponse
    {
        $data = $request->validate(['content' => ['nullable', 'string', 'max:1000000']]);

        return response()->json(['html' => $markdown->toHtml($data['content'] ?? '')]);
    }
}
