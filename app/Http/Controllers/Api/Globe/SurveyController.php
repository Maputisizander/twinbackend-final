<?php

namespace App\Http\Controllers\Api\Globe;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\GlobeNapBox;
use App\Models\GlobeNapPort;
use App\Models\GlobeNapSurvey;
use App\Models\GlobeNapSurveyItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SurveyController extends Controller
{
    public function index(GlobeNapBox $napBox)
    {
        return response()->json($napBox->surveys()->with('surveyedBy')->latest()->get());
    }

    public function store(Request $request, GlobeNapBox $napBox)
    {
        $data = $request->validate([
            'items'                    => 'required|array|min:1',
            'items.*.port_number'      => 'required|integer|min:1',
            'items.*.status'           => 'required|in:active,inactive,free',
            'items.*.subscriber_id'    => 'nullable|string',
            'items.*.subscriber_name'  => 'nullable|string',
            'items.*.account_number'   => 'nullable|string',
        ]);

        $survey = DB::transaction(function () use ($request, $napBox, $data) {
            $survey = GlobeNapSurvey::create([
                'nap_box_id'  => $napBox->id,
                'surveyed_by' => $request->user()->id,
                'surveyed_at' => now(),
                'status'      => 'pending',
            ]);

            foreach ($data['items'] as $item) {
                GlobeNapSurveyItem::create(array_merge($item, ['survey_id' => $survey->id]));
            }

            AuditLog::record('create', $survey, null, $survey->toArray());
            return $survey;
        });

        return response()->json($survey->load('items'), 201);
    }

    public function show(GlobeNapSurvey $survey)
    {
        return response()->json($survey->load(['napBox.pole', 'surveyedBy', 'items']));
    }

    public function submit(Request $request, GlobeNapSurvey $survey)
    {
        $old = $survey->toArray();

        DB::transaction(function () use ($survey) {
            $totalPorts    = $survey->napBox->port_count;
            $surveyedPorts = $survey->items->count();

            $status = $surveyedPorts >= $totalPorts ? 'complete' : 'partial';
            $survey->update(['status' => $status]);

            // Apply survey results to actual ports
            foreach ($survey->items as $item) {
                GlobeNapPort::where('nap_box_id', $survey->nap_box_id)
                    ->where('port_number', $item->port_number)
                    ->update([
                        'status'          => $item->status,
                        'subscriber_id'   => $item->subscriber_id,
                        'subscriber_name' => $item->subscriber_name,
                        'account_number'  => $item->account_number,
                        'surveyed_by'     => $survey->surveyed_by,
                        'surveyed_at'     => $survey->surveyed_at,
                        'updated_by'      => $survey->surveyed_by,
                    ]);
            }
        });

        AuditLog::record('update', $survey, $old, $survey->toArray());

        return response()->json($survey->fresh());
    }
}
