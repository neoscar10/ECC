<?php

namespace App\Services\Membership;

use App\Models\MembershipApplicationDraft;
use App\Models\MembershipApplication;
use App\Support\MetaOptionMapper;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

class ApplicationWizardService
{
    /**
     * Get the draft for the current session or user.
     */
    public function getDraft(): mixed
    {
        if (Auth::check()) {
            return MembershipApplication::where('user_id', Auth::id())
                ->where('status', 'draft')
                ->latest()
                ->first();
        }

        $sessionId = Session::getId();
        return MembershipApplicationDraft::where('session_id', $sessionId)->first();
    }

    /**
     * Get or create a draft for the current session or user.
     */
    public function getOrCreateDraft(): mixed
    {
        if (Auth::check()) {
            return MembershipApplication::firstOrCreate(
                ['user_id' => Auth::id(), 'status' => 'draft'],
                [
                    'current_step' => 'step-1'
                ]
            );
        }

        $sessionId = Session::getId();

        return MembershipApplicationDraft::firstOrCreate(
            ['session_id' => $sessionId],
            [
                'payload_json' => [],
                'current_step' => 0,
            ]
        );
    }

    /**
     * Save Step 3 (Old Step 1): Personal Details into the draft.
     */
    public function saveStep3PersonalDetails(array $data): mixed
    {
        $draft = $this->getOrCreateDraft();
        
        if ($draft instanceof MembershipApplication) {
            $draft->update([
                'personal_details_json' => $data,
                'current_step' => 'step-3'
            ]);
            return $draft;
        }

        $payload = $draft->payload_json;
        $payload['personal_details'] = $data;
        $payload['completed_step_1'] = true;

        $draft->update([
            'payload_json' => $payload,
            'current_step' => 3,
        ]);

        return $draft;
    }

    /**
     * Save Step 4 (Old Step 2): Cricket Profile into the draft.
     */
    public function saveStep4CricketProfile(array $data): mixed
    {
        $draft = $this->getOrCreateDraft();
        
        if ($draft instanceof MembershipApplication) {
            $draft->update([
                'cricket_profile_json' => [
                    'formats' => $data['formats'] ?? [],
                    'eras' => $data['eras'] ?? [],
                    'skipped' => $data['skipped'] ?? false,
                ],
                'current_step' => 'step-4'
            ]);
            return $draft;
        }

        $payload = $draft->payload_json;
        $payload['cricket_formats'] = $data['formats'] ?? [];
        $payload['cricket_eras'] = $data['eras'] ?? [];
        $payload['step2_skipped'] = $data['skipped'] ?? false;
        $payload['completed_step_2'] = true;

        $draft->update([
            'payload_json' => $payload,
            'current_step' => 4,
        ]);

        return $draft;
    }

    /**
     * Save Step 5 (Old Step 3): Collector Intent into the draft.
     */
    public function saveStep5CollectorIntent(array $data): mixed
    {
        $draft = $this->getOrCreateDraft();
        
        $focusCode = MetaOptionMapper::map($data['focus'] ?? 'RARITY', config('ecc_meta.collector_intent.focus'));
        $horizonCode = MetaOptionMapper::map($data['horizon_label'] ?? '', config('ecc_meta.collector_intent.investment_horizon'));
        $hasHistory = ($data['history'] ?? 'no') === 'yes';

        if ($draft instanceof MembershipApplication) {
            $draft->update([
                'collector_intent_json' => [
                    'history' => $data['history'] ?? 'no',
                    'has_acquired_memorabilia_before' => $hasHistory,
                    'focus' => $focusCode,
                    'investment_horizon' => $horizonCode,
                    'horizon_value' => (int)($data['horizon'] ?? 50),
                    'horizon_label' => $data['horizon_label'] ?? '',
                ],
                'current_step' => 'step-5'
            ]);
            return $draft;
        }

        $payload = $draft->payload_json;
        $payload['collector_history'] = $data['history'] ?? 'no';
        $payload['has_acquired_memorabilia_before'] = $hasHistory;
        $payload['collector_focus'] = $focusCode;
        $payload['collector_investment_horizon'] = $horizonCode;
        $payload['collector_horizon_value'] = (int)($data['horizon'] ?? 50);
        $payload['collector_horizon_label'] = $data['horizon_label'] ?? '';
        $payload['completed_step_3'] = true;

        $draft->update([
            'payload_json' => $payload,
            'current_step' => 5,
        ]);

        return $draft;
    }

    /**
     * Attach a guest session draft to a user's real application.
     */
    public function attachDraftToUser(int $userId): void
    {
        $sessionId = Session::getId();
        $sessionDraft = MembershipApplicationDraft::where('session_id', $sessionId)->first();

        if (!$sessionDraft) {
            return;
        }

        $userApplication = MembershipApplication::firstOrCreate(
            ['user_id' => $userId, 'status' => 'draft']
        );

        $payload = $sessionDraft->payload_json;

        $userApplication->update([
            'personal_details_json' => $payload['personal_details'] ?? $userApplication->personal_details_json,
            'cricket_profile_json' => [
                'formats' => $payload['cricket_formats'] ?? ($userApplication->cricket_profile_json['formats'] ?? []),
                'eras' => $payload['cricket_eras'] ?? ($userApplication->cricket_profile_json['eras'] ?? []),
                'skipped' => $payload['step2_skipped'] ?? ($userApplication->cricket_profile_json['skipped'] ?? false),
            ],
            'collector_intent_json' => [
                'history' => $payload['collector_history'] ?? ($userApplication->collector_intent_json['history'] ?? 'no'),
                'focus' => $payload['collector_focus'] ?? ($userApplication->collector_intent_json['focus'] ?? 'rarity'),
                'horizon_value' => $payload['collector_horizon_value'] ?? ($userApplication->collector_intent_json['horizon_value'] ?? 50),
                'horizon_label' => $payload['collector_horizon_label'] ?? ($userApplication->collector_intent_json['horizon_label'] ?? ''),
            ],
        ]);

        $sessionDraft->delete();
    }
}
