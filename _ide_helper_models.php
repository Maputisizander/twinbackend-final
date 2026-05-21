<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property int|null $user_id
 * @property string|null $company
 * @property string $action
 * @property string $model_type
 * @property int $model_id
 * @property array<array-key, mixed>|null $old_values
 * @property array<array-key, mixed>|null $new_values
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereCompany($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereModelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereModelType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereNewValues($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereOldValues($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereUserAgent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereUserId($value)
 */
	class AuditLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $pickup_request_id
 * @property int $from_warehouse_id
 * @property int $to_warehouse_id
 * @property int $dispatched_by
 * @property \Illuminate\Support\Carbon|null $dispatched_at
 * @property \Illuminate\Support\Carbon|null $arrived_at
 * @property int|null $accepted_by
 * @property \Illuminate\Support\Carbon|null $accepted_at
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $acceptedBy
 * @property-read \App\Models\User|null $dispatchedBy
 * @property-read \App\Models\Warehouse|null $fromWarehouse
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DeliveryItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\PickupRequest|null $pickupRequest
 * @property-read \App\Models\Warehouse|null $toWarehouse
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Delivery newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Delivery newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Delivery query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Delivery whereAcceptedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Delivery whereAcceptedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Delivery whereArrivedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Delivery whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Delivery whereDispatchedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Delivery whereDispatchedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Delivery whereFromWarehouseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Delivery whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Delivery whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Delivery wherePickupRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Delivery whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Delivery whereToWarehouseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Delivery whereUpdatedAt($value)
 */
	class Delivery extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $delivery_id
 * @property string $item_type
 * @property numeric $quantity
 * @property string $unit
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Delivery $delivery
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryItem whereDeliveryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryItem whereItemType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryItem whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeliveryItem whereUpdatedAt($value)
 */
	class DeliveryItem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $team_id
 * @property int $submitted_by
 * @property \Illuminate\Support\Carbon $report_date
 * @property int $total_tickets
 * @property int $total_completed
 * @property int $total_rejected
 * @property string $status
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property string|null $rejection_reason
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $approvedBy
 * @property-read \App\Models\User|null $submittedBy
 * @property-read \App\Models\Team|null $team
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GlobeTicket> $tickets
 * @property-read int|null $tickets_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeDailyReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeDailyReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeDailyReport query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeDailyReport whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeDailyReport whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeDailyReport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeDailyReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeDailyReport whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeDailyReport whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeDailyReport whereReportDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeDailyReport whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeDailyReport whereSubmittedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeDailyReport whereTeamId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeDailyReport whereTotalCompleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeDailyReport whereTotalRejected($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeDailyReport whereTotalTickets($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeDailyReport whereUpdatedAt($value)
 */
	class GlobeDailyReport extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $pole_id
 * @property string $nap_code
 * @property string $port_count
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Pole|null $pole
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GlobeNapPort> $ports
 * @property-read int|null $ports_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GlobeNapSurvey> $surveys
 * @property-read int|null $surveys_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GlobeTicket> $tickets
 * @property-read int|null $tickets_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapBox newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapBox newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapBox onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapBox query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapBox whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapBox whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapBox whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapBox whereNapCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapBox wherePoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapBox wherePortCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapBox whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapBox whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapBox withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapBox withoutTrashed()
 */
	class GlobeNapBox extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $nap_box_id
 * @property int $port_number
 * @property string $status
 * @property string|null $subscriber_id
 * @property string|null $subscriber_name
 * @property string|null $account_number
 * @property int|null $surveyed_by
 * @property \Illuminate\Support\Carbon|null $surveyed_at
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\GlobeNapBox|null $napBox
 * @property-read \App\Models\User|null $surveyedBy
 * @property-read \App\Models\User|null $updatedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapPort newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapPort newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapPort query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapPort whereAccountNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapPort whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapPort whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapPort whereNapBoxId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapPort wherePortNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapPort whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapPort whereSubscriberId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapPort whereSubscriberName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapPort whereSurveyedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapPort whereSurveyedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapPort whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapPort whereUpdatedBy($value)
 */
	class GlobeNapPort extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $nap_box_id
 * @property int $surveyed_by
 * @property \Illuminate\Support\Carbon|null $surveyed_at
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GlobeNapSurveyItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\GlobeNapBox|null $napBox
 * @property-read \App\Models\User|null $surveyedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapSurvey newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapSurvey newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapSurvey query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapSurvey whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapSurvey whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapSurvey whereNapBoxId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapSurvey whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapSurvey whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapSurvey whereSurveyedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapSurvey whereSurveyedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapSurvey whereUpdatedAt($value)
 */
	class GlobeNapSurvey extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $survey_id
 * @property int $port_number
 * @property string|null $subscriber_id
 * @property string|null $account_number
 * @property string|null $subscriber_name
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\GlobeNapSurvey $survey
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapSurveyItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapSurveyItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapSurveyItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapSurveyItem whereAccountNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapSurveyItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapSurveyItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapSurveyItem whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapSurveyItem wherePortNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapSurveyItem whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapSurveyItem whereSubscriberId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapSurveyItem whereSubscriberName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapSurveyItem whereSurveyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeNapSurveyItem whereUpdatedAt($value)
 */
	class GlobeNapSurveyItem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $ticket_id
 * @property int $lineman_id
 * @property string $wire_status
 * @property \Illuminate\Support\Carbon|null $teardown_date
 * @property string|null $before_photo
 * @property string|null $after_photo
 * @property string|null $pole_tag_photo
 * @property string $status
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property string|null $rejection_reason
 * @property string|null $notes
 * @property bool $offline_mode
 * @property \Illuminate\Support\Carbon|null $captured_at_device
 * @property \Illuminate\Support\Carbon|null $received_at_server
 * @property numeric|null $captured_lat
 * @property numeric|null $captured_lng
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $approvedBy
 * @property-read \App\Models\User|null $lineman
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GlobeTeardownReportSlot> $slots
 * @property-read int|null $slots_count
 * @property-read \App\Models\GlobeTicket|null $ticket
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReport query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReport whereAfterPhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReport whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReport whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReport whereBeforePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReport whereCapturedAtDevice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReport whereCapturedLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReport whereCapturedLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReport whereLinemanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReport whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReport whereOfflineMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReport wherePoleTagPhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReport whereReceivedAtServer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReport whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReport whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReport whereTeardownDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReport whereTicketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReport whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReport whereWireStatus($value)
 */
	class GlobeTeardownReport extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $teardown_report_id
 * @property int $pole_id
 * @property int $pole_cable_slot_id
 * @property string $slot_label
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PoleCableSlot $cableSlot
 * @property-read \App\Models\Pole|null $pole
 * @property-read \App\Models\GlobeTeardownReport $teardownReport
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReportSlot newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReportSlot newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReportSlot query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReportSlot whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReportSlot whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReportSlot wherePoleCableSlotId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReportSlot wherePoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReportSlot whereSlotLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReportSlot whereTeardownReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTeardownReportSlot whereUpdatedAt($value)
 */
	class GlobeTeardownReportSlot extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $ticket_number
 * @property int|null $subcontractor_id
 * @property int|null $team_id
 * @property int $pole_id
 * @property int|null $nap_box_id
 * @property int $created_by
 * @property int|null $claimed_by
 * @property \Illuminate\Support\Carbon|null $assigned_at
 * @property \Illuminate\Support\Carbon|null $claimed_at
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $claimedBy
 * @property-read \App\Models\User|null $createdBy
 * @property-read \App\Models\GlobeNapBox|null $napBox
 * @property-read \App\Models\Pole|null $pole
 * @property-read \App\Models\Subcontractor|null $subcontractor
 * @property-read \App\Models\Team|null $team
 * @property-read \App\Models\GlobeTeardownReport|null $teardownReport
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTicket newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTicket newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTicket onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTicket query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTicket whereAssignedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTicket whereClaimedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTicket whereClaimedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTicket whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTicket whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTicket whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTicket whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTicket whereNapBoxId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTicket whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTicket wherePoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTicket whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTicket whereSubcontractorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTicket whereTeamId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTicket whereTicketNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTicket whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTicket withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobeTicket withoutTrashed()
 */
	class GlobeTicket extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $from_warehouse_id
 * @property int $to_warehouse_id
 * @property int $requested_by
 * @property int|null $approved_by
 * @property string|null $approved_at
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $approvedBy
 * @property-read \App\Models\Delivery|null $delivery
 * @property-read \App\Models\Warehouse|null $fromWarehouse
 * @property-read \App\Models\User|null $requestedBy
 * @property-read \App\Models\Warehouse|null $toWarehouse
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PickupRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PickupRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PickupRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PickupRequest whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PickupRequest whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PickupRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PickupRequest whereFromWarehouseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PickupRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PickupRequest whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PickupRequest whereRequestedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PickupRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PickupRequest whereToWarehouseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PickupRequest whereUpdatedAt($value)
 */
	class PickupRequest extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $pole_code
 * @property string|null $barangay_code
 * @property numeric|null $lat
 * @property numeric|null $lng
 * @property string $skycable_status
 * @property \Illuminate\Support\Carbon|null $skycable_cleared_at
 * @property string $globe_status
 * @property \Illuminate\Support\Carbon|null $globe_cleared_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PsgcBarangay|null $barangay
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PoleCableSlot> $cableSlots
 * @property-read int|null $cable_slots_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GlobeNapBox> $napBoxes
 * @property-read int|null $nap_boxes_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pole newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pole newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pole onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pole query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pole whereBarangayCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pole whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pole whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pole whereGlobeClearedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pole whereGlobeStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pole whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pole whereLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pole whereLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pole wherePoleCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pole whereSkycableClearedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pole whereSkycableStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pole whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pole withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pole withoutTrashed()
 */
	class Pole extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $pole_id
 * @property string $slot_label
 * @property string $occupied_by
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Pole|null $pole
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PoleCableSlot newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PoleCableSlot newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PoleCableSlot query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PoleCableSlot whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PoleCableSlot whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PoleCableSlot whereOccupiedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PoleCableSlot wherePoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PoleCableSlot whereSlotLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PoleCableSlot whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PoleCableSlot whereUpdatedAt($value)
 */
	class PoleCableSlot extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $code
 * @property string $name
 * @property string $city_code
 * @property-read \App\Models\PsgcCity $city
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PsgcBarangay newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PsgcBarangay newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PsgcBarangay query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PsgcBarangay whereCityCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PsgcBarangay whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PsgcBarangay whereName($value)
 */
	class PsgcBarangay extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $code
 * @property string $name
 * @property string|null $province_code
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PsgcBarangay> $barangays
 * @property-read int|null $barangays_count
 * @property-read \App\Models\PsgcProvince|null $province
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PsgcCity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PsgcCity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PsgcCity query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PsgcCity whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PsgcCity whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PsgcCity whereProvinceCode($value)
 */
	class PsgcCity extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $code
 * @property string $name
 * @property string $region_code
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PsgcCity> $cities
 * @property-read int|null $cities_count
 * @property-read \App\Models\PsgcRegion $region
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PsgcProvince newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PsgcProvince newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PsgcProvince query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PsgcProvince whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PsgcProvince whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PsgcProvince whereRegionCode($value)
 */
	class PsgcProvince extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $code
 * @property string $name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PsgcProvince> $provinces
 * @property-read int|null $provinces_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PsgcRegion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PsgcRegion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PsgcRegion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PsgcRegion whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PsgcRegion whereName($value)
 */
	class PsgcRegion extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $pull_out_request_id
 * @property string $item_type
 * @property numeric $quantity
 * @property string $unit
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PullOutRequest $pullOutRequest
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PullOutItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PullOutItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PullOutItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PullOutItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PullOutItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PullOutItem whereItemType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PullOutItem wherePullOutRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PullOutItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PullOutItem whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PullOutItem whereUpdatedAt($value)
 */
	class PullOutItem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $warehouse_id
 * @property string $purpose
 * @property int $declared_by
 * @property int|null $approved_by
 * @property string|null $approved_at
 * @property string|null $destination
 * @property int|null $arrival_confirmed_by
 * @property \Illuminate\Support\Carbon|null $arrival_confirmed_at
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $approvedBy
 * @property-read \App\Models\User|null $arrivalConfirmedBy
 * @property-read \App\Models\User|null $declaredBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PullOutItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\Warehouse|null $warehouse
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PullOutRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PullOutRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PullOutRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PullOutRequest whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PullOutRequest whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PullOutRequest whereArrivalConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PullOutRequest whereArrivalConfirmedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PullOutRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PullOutRequest whereDeclaredBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PullOutRequest whereDestination($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PullOutRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PullOutRequest whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PullOutRequest wherePurpose($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PullOutRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PullOutRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PullOutRequest whereWarehouseId($value)
 */
	class PullOutRequest extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SkycableNode> $nodes
 * @property-read int|null $nodes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SkycableSite> $sites
 * @property-read int|null $sites_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableArea newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableArea newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableArea query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableArea whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableArea whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableArea whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableArea whereUpdatedAt($value)
 */
	class SkycableArea extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $node_id
 * @property int|null $team_id
 * @property int|null $subcontractor_id
 * @property int $submitted_by
 * @property \Illuminate\Support\Carbon $report_date
 * @property string $status
 * @property int|null $subcon_reviewed_by
 * @property \Illuminate\Support\Carbon|null $subcon_reviewed_at
 * @property int|null $backend_approved_by
 * @property \Illuminate\Support\Carbon|null $backend_approved_at
 * @property string|null $rejection_reason
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\SkycableNode|null $node
 * @property-read \App\Models\Subcontractor|null $subcontractor
 * @property-read \App\Models\User|null $submittedBy
 * @property-read \App\Models\Team|null $team
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SkycableTeardownReport> $teardownReports
 * @property-read int|null $teardown_reports_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableDailyReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableDailyReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableDailyReport query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableDailyReport whereBackendApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableDailyReport whereBackendApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableDailyReport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableDailyReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableDailyReport whereNodeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableDailyReport whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableDailyReport whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableDailyReport whereReportDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableDailyReport whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableDailyReport whereSubconReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableDailyReport whereSubconReviewedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableDailyReport whereSubcontractorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableDailyReport whereSubmittedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableDailyReport whereTeamId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableDailyReport whereUpdatedAt($value)
 */
	class SkycableDailyReport extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $area_id
 * @property string|null $barangay_code
 * @property int|null $subcontractor_id
 * @property int|null $team_id
 * @property string $name
 * @property string|null $label
 * @property string|null $full_label
 * @property string $status
 * @property string $data_source
 * @property string|null $source_file
 * @property \Illuminate\Support\Carbon|null $date_start
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property \Illuminate\Support\Carbon|null $date_finished
 * @property numeric $expected_cable
 * @property numeric $actual_cable
 * @property numeric $progress_percentage
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\SkycableArea $area
 * @property-read \App\Models\PsgcBarangay|null $barangay
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SkycableDailyReport> $dailyReports
 * @property-read int|null $daily_reports_count
 * @property-read \App\Models\SkycableSite|null $site
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SkycablePole> $skycablePoles
 * @property-read int|null $skycable_poles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SkycableSpan> $spans
 * @property-read int|null $spans_count
 * @property-read \App\Models\Subcontractor|null $subcontractor
 * @property-read \App\Models\Team|null $team
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableNode newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableNode newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableNode onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableNode query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableNode whereActualCable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableNode whereAreaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableNode whereBarangayCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableNode whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableNode whereDataSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableNode whereDateFinished($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableNode whereDateStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableNode whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableNode whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableNode whereExpectedCable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableNode whereFullLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableNode whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableNode whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableNode whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableNode whereProgressPercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableNode whereSourceFile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableNode whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableNode whereSubcontractorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableNode whereTeamId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableNode whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableNode withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableNode withoutTrashed()
 */
	class SkycableNode extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $node_id
 * @property int $pole_id
 * @property int $sequence
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\SkycableNode|null $node
 * @property-read \App\Models\Pole|null $pole
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SkycableSpan> $spansFrom
 * @property-read int|null $spans_from_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SkycableSpan> $spansTo
 * @property-read int|null $spans_to_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycablePole newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycablePole newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycablePole query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycablePole whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycablePole whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycablePole whereNodeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycablePole wherePoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycablePole whereSequence($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycablePole whereUpdatedAt($value)
 */
	class SkycablePole extends \Eloquent {}
}

namespace App\Models{
/**
 * @property-read \App\Models\SkycableArea|null $area
 * @property-read \App\Models\PsgcBarangay|null $barangay
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SkycableNode> $nodes
 * @property-read int|null $nodes_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSite newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSite newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSite onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSite query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSite withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSite withoutTrashed()
 */
	class SkycableSite extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $node_id
 * @property int $from_pole_id
 * @property int $to_pole_id
 * @property string|null $span_code
 * @property numeric $length_meters
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SkycableSpanComponent> $components
 * @property-read int|null $components_count
 * @property-read \App\Models\SkycablePole $fromPole
 * @property-read \App\Models\SkycableNode|null $node
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SkycableTeardownReport> $teardownReports
 * @property-read int|null $teardown_reports_count
 * @property-read \App\Models\SkycablePole $toPole
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSpan newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSpan newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSpan onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSpan query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSpan whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSpan whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSpan whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSpan whereFromPoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSpan whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSpan whereLengthMeters($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSpan whereNodeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSpan whereSpanCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSpan whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSpan whereToPoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSpan whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSpan withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSpan withoutTrashed()
 */
	class SkycableSpan extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $span_id
 * @property string $component_type
 * @property numeric $expected_count
 * @property numeric $actual_count
 * @property string $unit
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\SkycableSpan|null $span
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSpanComponent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSpanComponent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSpanComponent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSpanComponent whereActualCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSpanComponent whereComponentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSpanComponent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSpanComponent whereExpectedCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSpanComponent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSpanComponent whereSpanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSpanComponent whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableSpanComponent whereUpdatedAt($value)
 */
	class SkycableSpanComponent extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $teardown_report_id
 * @property string $photo_type
 * @property string $image_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\SkycableTeardownReport $teardownReport
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownPhoto newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownPhoto newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownPhoto query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownPhoto whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownPhoto whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownPhoto whereImagePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownPhoto wherePhotoType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownPhoto whereTeardownReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownPhoto whereUpdatedAt($value)
 */
	class SkycableTeardownPhoto extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $span_id
 * @property int|null $team_id
 * @property int $lineman_id
 * @property \Illuminate\Support\Carbon|null $start_time
 * @property \Illuminate\Support\Carbon|null $end_time
 * @property int|null $duration_minutes
 * @property numeric $expected_cable
 * @property numeric $actual_cable
 * @property string|null $before_photo
 * @property string|null $after_photo
 * @property string|null $pole_tag_photo
 * @property string|null $bunching_photo
 * @property string $status
 * @property int|null $subcon_reviewed_by
 * @property \Illuminate\Support\Carbon|null $subcon_reviewed_at
 * @property int|null $backend_approved_by
 * @property \Illuminate\Support\Carbon|null $backend_approved_at
 * @property string|null $rejection_reason
 * @property string|null $notes
 * @property bool $offline_mode
 * @property \Illuminate\Support\Carbon|null $captured_at_device
 * @property \Illuminate\Support\Carbon|null $received_at_server
 * @property numeric|null $captured_lat
 * @property numeric|null $captured_lng
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $lineman
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SkycableTeardownPhoto> $photos
 * @property-read int|null $photos_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SkycableTeardownReportSlot> $slots
 * @property-read int|null $slots_count
 * @property-read \App\Models\SkycableSpan|null $span
 * @property-read \App\Models\Team|null $team
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReport query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReport whereActualCable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReport whereAfterPhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReport whereBackendApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReport whereBackendApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReport whereBeforePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReport whereBunchingPhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReport whereCapturedAtDevice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReport whereCapturedLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReport whereCapturedLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReport whereDurationMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReport whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReport whereExpectedCable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReport whereLinemanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReport whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReport whereOfflineMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReport wherePoleTagPhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReport whereReceivedAtServer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReport whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReport whereSpanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReport whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReport whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReport whereSubconReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReport whereSubconReviewedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReport whereTeamId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReport whereUpdatedAt($value)
 */
	class SkycableTeardownReport extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $teardown_report_id
 * @property int $pole_id
 * @property int $pole_cable_slot_id
 * @property string $slot_label
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PoleCableSlot $cableSlot
 * @property-read \App\Models\Pole|null $pole
 * @property-read \App\Models\SkycableTeardownReport $teardownReport
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReportSlot newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReportSlot newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReportSlot query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReportSlot whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReportSlot whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReportSlot wherePoleCableSlotId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReportSlot wherePoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReportSlot whereSlotLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReportSlot whereTeardownReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SkycableTeardownReportSlot whereUpdatedAt($value)
 */
	class SkycableTeardownReportSlot extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $company
 * @property string $name
 * @property string|null $contact_person
 * @property string|null $contact_number
 * @property string|null $address
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Team> $teams
 * @property-read int|null $teams_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Warehouse> $warehouses
 * @property-read int|null $warehouses_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subcontractor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subcontractor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subcontractor onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subcontractor query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subcontractor whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subcontractor whereCompany($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subcontractor whereContactNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subcontractor whereContactPerson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subcontractor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subcontractor whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subcontractor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subcontractor whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subcontractor whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subcontractor whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subcontractor withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subcontractor withoutTrashed()
 */
	class Subcontractor extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $ticket_number
 * @property string $company
 * @property int $submitted_by
 * @property int|null $assigned_to
 * @property string $subject
 * @property string $description
 * @property string $priority
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property \Illuminate\Support\Carbon|null $closed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $assignedTo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SupportTicketAttachment> $attachments
 * @property-read int|null $attachments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SupportTicketMessage> $messages
 * @property-read int|null $messages_count
 * @property-read \App\Models\User|null $submittedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket whereAssignedTo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket whereClosedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket whereCompany($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket whereResolvedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket whereSubmittedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket whereTicketNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicket withoutTrashed()
 */
	class SupportTicket extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $ticket_id
 * @property int|null $message_id
 * @property string $file_path
 * @property string $file_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\SupportTicketMessage|null $message
 * @property-read \App\Models\SupportTicket|null $ticket
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketAttachment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketAttachment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketAttachment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketAttachment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketAttachment whereFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketAttachment whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketAttachment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketAttachment whereMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketAttachment whereTicketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketAttachment whereUpdatedAt($value)
 */
	class SupportTicketAttachment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $ticket_id
 * @property int $sender_id
 * @property string $message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $sender
 * @property-read \App\Models\SupportTicket|null $ticket
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketMessage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketMessage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketMessage whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketMessage whereSenderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketMessage whereTicketId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SupportTicketMessage whereUpdatedAt($value)
 */
	class SupportTicketMessage extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $company
 * @property int|null $subcontractor_id
 * @property string $name
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $members
 * @property-read int|null $members_count
 * @property-read \App\Models\Subcontractor|null $subcontractor
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereCompany($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereSubcontractorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team withoutTrashed()
 */
	class Team extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $company
 * @property string $role
 * @property array<array-key, mixed>|null $project_access
 * @property int|null $subcontractor_id
 * @property int|null $team_id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $password
 * @property string|null $cellphone
 * @property string|null $address
 * @property string|null $profile_photo
 * @property numeric|null $current_gps_lat
 * @property numeric|null $current_gps_lng
 * @property \Illuminate\Support\Carbon|null $last_seen_at
 * @property \Illuminate\Support\Carbon|null $last_login
 * @property string $status
 * @property bool $password_reset_required
 * @property \Illuminate\Support\Carbon|null $temp_password_set_at
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $full_name
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\Subcontractor|null $subcontractor
 * @property-read \App\Models\Team|null $team
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCellphone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCompany($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCurrentGpsLat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCurrentGpsLng($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastSeenAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePasswordResetRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereProfilePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereProjectAccess($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereSubcontractorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTeamId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTempPasswordSetAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
 */
	class User extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $subcontractor_id
 * @property string $name
 * @property string $type
 * @property string|null $address
 * @property numeric $sqm
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WarehouseReceipt> $receipts
 * @property-read int|null $receipts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WarehouseStock> $stocks
 * @property-read int|null $stocks_count
 * @property-read \App\Models\Subcontractor|null $subcontractor
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereSqm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereSubcontractorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Warehouse withoutTrashed()
 */
	class Warehouse extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $warehouse_id
 * @property int|null $subcontractor_id
 * @property int|null $node_id
 * @property int $received_by
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon $receipt_date
 * @property string|null $approved_at
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $approvedBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WarehouseReceiptItem> $items
 * @property-read int|null $items_count
 * @property-read \App\Models\SkycableNode|null $node
 * @property-read \App\Models\User|null $receivedBy
 * @property-read \App\Models\Subcontractor|null $subcontractor
 * @property-read \App\Models\Warehouse|null $warehouse
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseReceipt newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseReceipt newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseReceipt query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseReceipt whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseReceipt whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseReceipt whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseReceipt whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseReceipt whereNodeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseReceipt whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseReceipt whereReceiptDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseReceipt whereReceivedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseReceipt whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseReceipt whereSubcontractorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseReceipt whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseReceipt whereWarehouseId($value)
 */
	class WarehouseReceipt extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $receipt_id
 * @property string $item_type
 * @property numeric $quantity
 * @property string $unit
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\WarehouseReceipt $receipt
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseReceiptItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseReceiptItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseReceiptItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseReceiptItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseReceiptItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseReceiptItem whereItemType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseReceiptItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseReceiptItem whereReceiptId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseReceiptItem whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseReceiptItem whereUpdatedAt($value)
 */
	class WarehouseReceiptItem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $warehouse_id
 * @property string $item_type
 * @property numeric $quantity
 * @property string $unit
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Warehouse|null $warehouse
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseStock newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseStock newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseStock query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseStock whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseStock whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseStock whereItemType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseStock whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseStock whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseStock whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseStock whereWarehouseId($value)
 */
	class WarehouseStock extends \Eloquent {}
}

