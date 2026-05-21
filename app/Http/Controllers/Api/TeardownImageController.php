<?php

namespace App\Http\Controllers\Api;

use App\Http\Concerns\StoresPhotos;
use App\Http\Controllers\Controller;
use App\Models\Pole;
use App\Models\PoleTeardownImage;
use App\Models\SkycableArea;
use App\Models\SkycableNode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TeardownImageController extends Controller
{
    use StoresPhotos;

    public function upload(Request $request)
    {
        $request->validate([
            'report_id'      => 'nullable|integer',
            'pole_id'        => 'required|integer',
            'area_id'        => 'nullable|integer',
            'node_id'        => 'nullable|integer',
            'pole_code'      => 'required|string',
            'image_type'     => 'required|string|in:before,after,pole_tag,bunching',
            'span_id'        => 'nullable|integer',
            'pole_tag'       => 'nullable|string',
            'inventory_type' => 'required|string|in:skycable,globe',
            'image'          => 'required|image|max:10240', // 10MB max
        ]);

        $node = $request->node_id ? SkycableNode::find($request->node_id) : null;
        $area = $request->area_id ? SkycableArea::find($request->area_id) : ($node ? $node->area : null);
        $areaId = $area ? $area->id : $request->area_id;

        $areaName  = $this->sanitizePath($area ? $area->name : 'Unknown_Area');
        $nodeName  = $this->sanitizePath($node ? $node->name : 'Unknown_Node');
        $poleCode  = $this->sanitizePath($request->pole_code);
        $poleId    = $request->pole_id;
        $imageType = $request->image_type;

        if ($imageType === 'bunching') {
            // spans/{span_id}/{span_id}_bunching.jpg
            $spanId     = $this->sanitizePath($request->span_id ?? 'unknown');
            $folderPath = "spans/{$spanId}";
            $fileName   = "{$spanId}_bunching.jpg";
        } else {
            // {area}/{node}/{pole_code}/{pole_id}_before.jpg  (or after / poletag)
            $typeSlug   = $imageType === 'pole_tag' ? 'poletag' : $imageType;
            $folderPath = "{$areaName}/{$nodeName}/{$poleCode}";
            $fileName   = "{$poleId}_{$typeSlug}.jpg";
        }

        $fullPath = "{$folderPath}/{$fileName}";

        $storedPath = $this->storePhoto($request->file('image'), 'teardown', 1280, $fullPath);

        $imageData = PoleTeardownImage::create([
            'report_id'      => $request->report_id,
            'pole_id'        => $request->pole_id,
            'area_id'        => $request->area_id,
            'node_id'        => $request->node_id,
            'pole_code'      => $request->pole_code,
            'image_type'     => $request->image_type,
            'pole_tag'       => $request->pole_tag,
            'file_path'      => $storedPath,
            'inventory_type' => $request->inventory_type,
        ]);

        return response()->json([
            'message' => 'Image uploaded successfully',
            'data'    => $imageData
        ], 201);
    }

    public function fetchByNode(Request $request, $nodeId)
    {
        $images = PoleTeardownImage::where('node_id', $nodeId)
            ->when($request->inventory_type, function($q) use ($request) {
                return $q->where('inventory_type', $request->inventory_type);
            })
            ->get();

        return response()->json([
            'data' => $images
        ]);
    }

    public function fetchByReport(Request $request, $reportId)
    {
        $images = PoleTeardownImage::where('report_id', $reportId)
            ->when($request->inventory_type, function($q) use ($request) {
                return $q->where('inventory_type', $request->inventory_type);
            })
            ->get();

        return response()->json([
            'data' => $images
        ]);
    }

    private function sanitizePath($name)
    {
        // Remove characters that are not alphanumeric, spaces, underscores, or hyphens
        return preg_replace('/[^A-Za-z0-9_\- ]/', '', $name);
    }
}
