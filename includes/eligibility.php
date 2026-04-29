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
            'dengue',
            'typhoid',
            'jaundice',
            'yellow fever',
            'tuberculosis',
            'heavy medication',
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

    // Check for permanent medical conditions (not eligible)
    $permanentConditions = [
        'hiv_status' => 'HIV/AIDS',
        'hepatitis_b_status' => 'Hepatitis B',
        'hepatitis_c_status' => 'Hepatitis C',
        'syphilis_status' => 'Syphilis',
        'cancer_status' => 'Cancer',
        'heart_disease_status' => 'Serious Heart Disease',
        'liver_disease_status' => 'Serious Liver Disease',
        'kidney_disease_status' => 'Serious Kidney Disease',
        'thalassemia_status' => 'Thalassemia',
        'hemophilia_status' => 'Hemophilia'
    ];

    foreach ($permanentConditions as $field => $condition) {
        if (isset($user[$field]) && $user[$field] == 1) {
            $result['status'] = 'not eligible';
            $result['reason'] = "Medical condition '{$condition}' disqualifies from blood donation.";
            return $result;
        }
    }

    // Check for temporary medical conditions (defer donation)
    $temporaryConditions = [
        'malaria_status' => ['Malaria', 180],  // 6 months
        'dengue_status' => ['Dengue', 180],    // 6 months
        'typhoid_status' => ['Typhoid', 180],  // 6 months
        'jaundice_status' => ['Jaundice (Yellow Fever)', 180],  // 6 months
        'tuberculosis_status' => ['Tuberculosis', 180]  // 6 months
    ];

    foreach ($temporaryConditions as $field => $details) {
        if (isset($user[$field]) && $user[$field] == 1) {
            $result['status'] = 'temporarily deferred';
            $result['reason'] = "{$details[0]} - donor must wait approximately {$details[1]} days (6 months) before donation.";
            $result['next_eligible_date'] = date('Y-m-d', strtotime("+{$details[1]} days"));
            return $result;
        }
    }

    // Check for tattoo/piercing (6-month restriction)
    if (isset($user['tattoo_piercing_status']) && $user['tattoo_piercing_status'] == 1) {
        $tattooDate = isset($user['tattoo_piercing_date']) && $user['tattoo_piercing_date'] !== ''
            ? $user['tattoo_piercing_date']
            : null;

        if ($tattooDate) {
            $tattooTimestamp = strtotime($tattooDate);
            $sixMonthsLater = strtotime('+6 months', $tattooTimestamp);
            
            if (time() < $sixMonthsLater) {
                $nextEligibleDate = date('Y-m-d', $sixMonthsLater);
                $result['status'] = 'temporarily deferred';
                $result['reason'] = 'Tattoo or piercing received - must wait 6 months before donation.';
                $result['next_eligible_date'] = $nextEligibleDate;
                return $result;
            }
        }
    }

    // Check for recent surgery
    if (isset($user['recent_surgery_status']) && $user['recent_surgery_status'] == 1) {
        $surgeryDate = isset($user['recent_surgery_date']) && $user['recent_surgery_date'] !== ''
            ? $user['recent_surgery_date']
            : null;

        if ($surgeryDate) {
            $surgeryTimestamp = strtotime($surgeryDate);
            $requiredDays = 180;  // 6 months for surgery
            $nextEligibleDate = strtotime("+{$requiredDays} days", $surgeryTimestamp);
            
            if (time() < $nextEligibleDate) {
                $nextEligibleDate = date('Y-m-d', $nextEligibleDate);
                $result['status'] = 'temporarily deferred';
                $result['reason'] = 'Recent surgery - must wait at least 6 months before donation.';
                $result['next_eligible_date'] = $nextEligibleDate;
                return $result;
            }
        }
    }

    // Check for heavy medication usage
    if (isset($user['heavy_medication_status']) && $user['heavy_medication_status'] == 1) {
        $result['status'] = 'temporarily deferred';
        $result['reason'] = 'Heavy medication usage - must discontinue medication for appropriate period before donation.';
        return $result;
    }

    // Check for antibiotics (typically 7 days deferral)
    if (isset($user['antibiotics_status']) && $user['antibiotics_status'] == 1) {
        $result['status'] = 'temporarily deferred';
        $result['reason'] = 'Currently on antibiotics - must wait at least 7 days after completion before donation.';
        return $result;
    }

    // Check for pregnancy (permanent deferral during pregnancy)
    if (isset($user['pregnancy_status']) && $user['pregnancy_status'] == 1) {
        $result['status'] = 'not eligible';
        $result['reason'] = 'Pregnant women cannot donate blood during pregnancy.';
        return $result;
    }

    // Check for breastfeeding (temporary deferral)
    if (isset($user['breastfeeding_status']) && $user['breastfeeding_status'] == 1) {
        $result['status'] = 'temporarily deferred';
        $result['reason'] = 'Breastfeeding women must wait until they stop breastfeeding before donation.';
        return $result;
    }

    // Check for menstruation (temporary deferral)
    if (isset($user['menstruation_status']) && $user['menstruation_status'] == 1) {
        $result['status'] = 'temporarily deferred';
        $result['reason'] = 'Women experiencing menstruation should wait until menstruation stops before donation.';
        return $result;
    }

    // Check medical history keywords (as fallback)
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