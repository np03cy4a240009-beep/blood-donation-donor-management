<?php

require_once __DIR__ . '/functions.php';

function normalizeText($value) {
    return strtolower(trim((string)$value));
}

function containsAnyKeyword($text, array $keywords) {
    $text = normalizeText($text);

    foreach ($keywords as $keyword) {
        if ($keyword !== '' && strpos($text, strtolower($keyword)) !== false) {
            return true;
        }
    }

    return false;
}

function getNepalEligibilityRules() {
    return [
        'min_age' => 18,
        'max_age' => 60,
        'min_weight' => 45,
        'whole_blood_gap_days' => 90,
        'female_recommended_gap_days' => 120,

        'permanent_deferral_keywords' => [
            'hiv', 'aids',
            'hepatitis b', 'hepatitis c', 'hepatitis',
            'syphilis',
            'cancer',
            'serious heart disease', 'heart disease',
            'serious liver disease', 'liver disease',
            'serious kidney disease', 'kidney disease',
            'thalassemia',
            'hemophilia',
            'severe diabetes',
            'blood disorder'
        ],

        'temporary_deferral_keywords' => [
            'pregnant',
            'pregnancy',
            'breastfeeding',
            'menstruation',
            'recent illness',
            'infection',
            'recent surgery',
            'vaccination',
            'antibiotics',
            'tattoo',
            'piercing',
            'malaria',
            'recent transfusion',
            'alcohol',
            'drug use',
            'unsafe sexual'
        ],

        'rare_groups' => getRareBloodGroups()
    ];
}

function evaluateDonorEligibility(array $user) {
    $rules = getNepalEligibilityRules();

    $age = isset($user['age']) && $user['age'] !== '' ? (int)$user['age'] : null;
    $weight = isset($user['weight']) && $user['weight'] !== '' ? (float)$user['weight'] : null;
    $gender = trim((string)($user['gender'] ?? ''));
    $bloodGroup = trim((string)($user['blood_group'] ?? ''));
    $medicalHistory = trim((string)($user['medical_history'] ?? ''));
    $lastDonated = trim((string)($user['last_donated'] ?? ''));

    $result = [
        'status' => 'eligible',
        'reason' => 'Eligible under current stored data.',
        'notes' => [],
        'next_eligible_date' => null,
        'priority_tag' => isRareBloodGroup($bloodGroup) ? 'rare priority' : 'standard'
    ];

    if ($age === null || $age < $rules['min_age'] || $age > $rules['max_age']) {
        $result['status'] = 'not eligible';
        $result['reason'] = 'Age must be between 18 and 60 years.';
        return $result;
    }

    if ($weight === null || $weight < $rules['min_weight']) {
        $result['status'] = 'not eligible';
        $result['reason'] = 'Weight must be at least 45 kg.';
        return $result;
    }

    if (containsAnyKeyword($medicalHistory, $rules['permanent_deferral_keywords'])) {
        $result['status'] = 'not eligible';
        $result['reason'] = 'Medical history indicates permanent or long-term deferral.';
        return $result;
    }

    if (containsAnyKeyword($medicalHistory, $rules['temporary_deferral_keywords'])) {
        $result['status'] = 'temporarily deferred';
        $result['reason'] = 'Medical history indicates temporary deferral condition.';
        return $result;
    }

    if ($lastDonated !== '') {
        $lastTimestamp = strtotime($lastDonated);

        if ($lastTimestamp !== false) {
            $requiredGap = strtolower($gender) === 'female'
                ? $rules['female_recommended_gap_days']
                : $rules['whole_blood_gap_days'];

            $nextEligible = date('Y-m-d', strtotime("+{$requiredGap} days", $lastTimestamp));
            $result['next_eligible_date'] = $nextEligible;

            if (date('Y-m-d') < $nextEligible) {
                $result['status'] = 'temporarily deferred';
                $result['reason'] = 'Required donation gap has not been completed yet.';
                return $result;
            }
        }
    }

    if (isRareBloodGroup($bloodGroup)) {
        $result['notes'][] = 'Rare blood group: keep donor on priority contact list.';
    }

    return $result;
}

function getCompatibleDonorsForRequest($bloodGroup) {
    $map = [
        'O-' => ['O-'],
        'O+' => ['O+', 'O-'],
        'A-' => ['A-', 'O-'],
        'A+' => ['A+', 'A-', 'O+', 'O-'],
        'B-' => ['B-', 'O-'],
        'B+' => ['B+', 'B-', 'O+', 'O-'],
        'AB-' => ['AB-', 'A-', 'B-', 'O-'],
        'AB+' => ['AB+', 'AB-', 'A+', 'A-', 'B+', 'B-', 'O+', 'O-'],

        'A1+' => ['A1+', 'A1-', 'A+', 'A-', 'O+', 'O-'],
        'A1-' => ['A1-', 'A-', 'O-'],
        'A2+' => ['A2+', 'A2-', 'A+', 'A-', 'O+', 'O-'],
        'A2-' => ['A2-', 'A-', 'O-'],
        'A1B+' => ['A1B+', 'A1B-', 'AB+', 'AB-', 'A1+', 'A1-', 'B+', 'B-', 'O+', 'O-'],
        'A1B-' => ['A1B-', 'AB-', 'A1-', 'B-', 'O-'],
        'A2B+' => ['A2B+', 'A2B-', 'AB+', 'AB-', 'A2+', 'A2-', 'B+', 'B-', 'O+', 'O-'],
        'A2B-' => ['A2B-', 'AB-', 'A2-', 'B-', 'O-'],

        'Bombay (Oh)' => ['Bombay (Oh)'],
        'Rh-null' => ['Rh-null']
    ];

    return $map[$bloodGroup] ?? [$bloodGroup];
}

function getCompatibleRecipientsForDonor($bloodGroup) {
    $map = [
        'O-' => ['O-', 'O+', 'A-', 'A+', 'B-', 'B+', 'AB-', 'AB+'],
        'O+' => ['O+', 'A+', 'B+', 'AB+'],
        'A-' => ['A-', 'A+', 'AB-', 'AB+'],
        'A+' => ['A+', 'AB+'],
        'B-' => ['B-', 'B+', 'AB-', 'AB+'],
        'B+' => ['B+', 'AB+'],
        'AB-' => ['AB-', 'AB+'],
        'AB+' => ['AB+'],

        'A1+' => ['A1+', 'A1B+', 'AB+'],
        'A1-' => ['A1-', 'A1+', 'A1B-', 'A1B+', 'AB-', 'AB+'],
        'A2+' => ['A2+', 'A2B+', 'AB+'],
        'A2-' => ['A2-', 'A2+', 'A2B-', 'A2B+', 'AB-', 'AB+'],
        'A1B+' => ['A1B+', 'AB+'],
        'A1B-' => ['A1B-', 'A1B+', 'AB-', 'AB+'],
        'A2B+' => ['A2B+', 'AB+'],
        'A2B-' => ['A2B-', 'A2B+', 'AB-', 'AB+'],

        'Bombay (Oh)' => ['Bombay (Oh)'],
        'Rh-null' => ['Rh-null']
    ];

    return $map[$bloodGroup] ?? [$bloodGroup];
}
?>