<?php
class StoreMemberController
{
    public function index(): void
    {
        Auth::requireOwner();
        $storeId = Auth::storeId();

        $members = StoreMember::allForStore($storeId);

        $insuranceByMember = [];
        foreach (EmployeeInsuranceSetting::allForStore($storeId) as $ins) {
            $insuranceByMember[(int)$ins['store_member_id']] = $ins;
        }

        $actualSummary = AttendanceLog::batchActualHoursSummary($storeId);

        render('members/index', [
            'title'             => '직원 관리',
            'members'           => $members,
            'insuranceByMember' => $insuranceByMember,
            'actualSummary'     => $actualSummary,
        ]);
    }

    /** 근로계약서 작성 폼 (GET) / 저장 후 print 뷰 리다이렉트 (POST) */
    public function contract(): void
    {
        Auth::requireOwner();
        $storeId = Auth::storeId();
        $id      = (int)($_GET['id'] ?? 0);
        $member  = StoreMember::find($id, $storeId);

        if (!$member) {
            flash('error', '직원을 찾을 수 없습니다.');
            redirect(url('members'));
        }

        $store    = Store::findOwned(Auth::storeId(), Auth::ownerId());
        $errors   = [];
        $formData = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $formData = $this->extractContractPost();

            if (empty(trim($formData['employer_name'])))   $errors[] = '사업주명을 입력하세요.';
            if (empty(trim($formData['employee_name'])))   $errors[] = '근로자 실명을 입력하세요.';
            if (empty($formData['contract_start_date']))   $errors[] = '계약 시작일을 입력하세요.';
            if (empty($formData['work_start_time']))       $errors[] = '근무 시작시간을 입력하세요.';
            if (empty($formData['work_end_time']))         $errors[] = '근무 종료시간을 입력하세요.';

            if (!$errors) {
                $contractId = EmploymentContract::create($storeId, $id, $formData, Auth::id());
                redirect(url('members', 'contract_view', ['id' => $id, 'contract_id' => $contractId]));
            }
        }

        $history          = EmploymentContract::allForMember($storeId, $id);
        $insuranceSetting = EmployeeInsuranceSetting::findByMember($storeId, $id);

        // GET: 이전 계약서가 있으면 해당 내용으로, 없으면 직원 등록 정보로 pre-fill
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $latest   = EmploymentContract::latestFormData($storeId, $id);
            $formData = $latest ?? [];
            // 직원 등록 정보는 항상 현재 값으로 덮어씀 (이전 계약서 있어도 동일)
            $formData['hourly_wage']             = $member['hourly_wage']             ?? '';
            $formData['weekly_scheduled_hours']  = $member['weekly_scheduled_hours']  ?? '';
            $formData['weekly_scheduled_days']   = $member['weekly_scheduled_days']   ?? '';
            $formData['contract_start_date']     = $member['employment_start_date']   ?? date('Y-m-d');
            $formData['contract_end_date']       = $member['employment_end_date']     ?? '';
            $formData['employee_name']           = $member['name']                    ?? '';
            $formData['employee_phone']          = $member['phone']                   ?? '';
            if (!empty($member['work_start_time']))
                $formData['work_start_time'] = $member['work_start_time'];
            if (!empty($member['work_end_time']))
                $formData['work_end_time']   = $member['work_end_time'];
            if (isset($member['daily_break_minutes']) && $member['daily_break_minutes'] !== null)
                $formData['break_minutes']   = (int)$member['daily_break_minutes'];
            // 최초 작성이면 사업장 기본 정보도 채움
            if (!$latest) {
                $formData['business_name']   = $store['store_name'] ?? '';
            }
        }

        $settings = Setting::get();
        render('members/contract_form', [
            'title'            => '근로계약서 작성 — ' . h($member['name']),
            'member'           => $member,
            'store'            => $store,
            'errors'           => $errors,
            'formData'         => $formData,
            'history'          => $history,
            'insuranceSetting' => $insuranceSetting,
            'minWage'          => (int)($settings['minimum_wage'] ?? 0),
        ]);
    }

    /** 근로계약서 인쇄 뷰 */
    public function contractView(): void
    {
        Auth::requireOwner();
        $storeId    = Auth::storeId();
        $contractId = (int)($_GET['contract_id'] ?? 0);
        $contract   = EmploymentContract::find($contractId, $storeId);

        if (!$contract) {
            flash('error', '계약서를 찾을 수 없습니다.');
            redirect(url('members'));
        }

        EmploymentContract::markDownloaded($contractId, $storeId);
        $store = Store::findOwned(Auth::storeId(), Auth::ownerId());

        render('members/contract_print', [
            'title'    => '근로계약서',
            'contract' => $contract,
            'fd'       => $contract['form_data'],
            'store'    => $store,
        ], 'payslip_layout');
    }

    /** 미성년자 동의서 폼 (GET) / 저장 후 리다이렉트 (POST) */
    public function minorConsent(): void
    {
        Auth::requireOwner();
        $storeId = Auth::storeId();
        $id      = (int)($_GET['id'] ?? 0);
        $member  = StoreMember::find($id, $storeId);

        if (!$member) {
            flash('error', '직원을 찾을 수 없습니다.');
            redirect(url('members'));
        }

        $store    = Store::findOwned(Auth::storeId(), Auth::ownerId());
        $errors   = [];
        $formData = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $formData = array_map('trim', $_POST);
            unset($formData['csrf_token']);

            if (empty($formData['employee_name']))   $errors[] = '근로자 실명을 입력하세요.';
            if (empty($formData['date_of_birth']))   $errors[] = '생년월일을 입력하세요.';
            if (empty($formData['guardian_name']))   $errors[] = '친권자/후견인 성명을 입력하세요.';
            if (empty($formData['guardian_relation'])) $errors[] = '근로자와의 관계를 입력하세요.';

            if (!$errors) {
                $consentId = MinorConsentForm::create($storeId, $id, $formData, Auth::id());
                redirect(url('members', 'minor_consent_view', ['id' => $id, 'consent_id' => $consentId]));
            }
        }

        $history = MinorConsentForm::allForMember($storeId, $id);

        render('members/minor_consent_form', [
            'title'    => '친권자(후견인) 동의서 — ' . h($member['name']),
            'member'   => $member,
            'store'    => $store,
            'errors'   => $errors,
            'formData' => $formData,
            'history'  => $history,
        ]);
    }

    /** 미성년자 동의서 인쇄 뷰 */
    public function minorConsentView(): void
    {
        Auth::requireOwner();
        $storeId   = Auth::storeId();
        $consentId = (int)($_GET['consent_id'] ?? 0);
        $consent   = MinorConsentForm::find($consentId, $storeId);

        if (!$consent) {
            flash('error', '동의서를 찾을 수 없습니다.');
            redirect(url('members'));
        }

        MinorConsentForm::markDownloaded($consentId, $storeId);

        render('members/minor_consent_print', [
            'title'   => '친권자(후견인) 동의서',
            'consent' => $consent,
            'fd'      => $consent['form_data'],
        ], 'payslip_layout');
    }

    private function extractContractPost(): array
    {
        $p = $_POST;
        return [
            'business_name'                 => trim($p['business_name']                 ?? ''),
            'business_registration_number'  => trim($p['business_registration_number']  ?? ''),
            'employer_name'                 => trim($p['employer_name']                 ?? ''),
            'employer_address'              => trim($p['employer_address']              ?? ''),
            'employer_phone'                => trim($p['employer_phone']                ?? ''),
            'employee_name'          => trim($p['employee_name']           ?? ''),
            'employee_address'       => trim($p['employee_address']        ?? ''),
            'employee_phone'         => trim($p['employee_phone']          ?? ''),
            'work_location'          => trim($p['work_location']           ?? ''),
            'job_duties'             => trim($p['job_duties']              ?? ''),
            'contract_start_date'    => $p['contract_start_date']          ?? '',
            'contract_end_date'      => $p['contract_end_date']            ?? '',
            'work_days'              => implode(',', (array)($p['work_days'] ?? [])),
            'work_start_time'        => $p['work_start_time']              ?? '',
            'work_end_time'          => $p['work_end_time']                ?? '',
            'break_minutes'          => (int)($p['break_minutes']          ?? 0),
            'hourly_wage'            => (int)($p['hourly_wage']            ?? 0),
            'weekly_scheduled_hours' => (float)($p['weekly_scheduled_hours'] ?? 0),
            'weekly_scheduled_days'  => (int)($p['weekly_scheduled_days']  ?? 0),
            'weekly_holiday_day'     => trim($p['weekly_holiday_day']      ?? '일요일'),
            'other_holidays'         => trim($p['other_holidays']          ?? ''),
            'pay_day'                => (int)($p['pay_day']                ?? 0),
            'pay_method'             => trim($p['pay_method']              ?? '계좌이체'),
            'insurance_pension'      => isset($p['insurance_pension'])     ? 1 : 0,
            'insurance_health'       => isset($p['insurance_health'])      ? 1 : 0,
            'insurance_employment'   => isset($p['insurance_employment'])  ? 1 : 0,
            'issue_date'             => $p['issue_date']                   ?? date('Y-m-d'),
            'include_annual_leave'   => isset($p['include_annual_leave'])  ? 1 : 0,
        ];
    }

    public function add(): void
    {
        Auth::requireOwner();
        render('members/add_method', ['title' => '직원 추가']);
    }

    public function linkAccount(): void
    {
        Auth::requireOwner();
        $storeId = Auth::storeId();
        $errors  = [];
        $found   = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $action = $_POST['action'] ?? '';

            if ($action === 'search') {
                $query = trim($_POST['query'] ?? '');
                if (!$query) {
                    $errors[] = '사용자 ID 또는 이메일을 입력하세요.';
                } else {
                    $found = is_numeric($query)
                        ? User::find((int)$query)
                        : User::findByEmail($query);

                    if (!$found) {
                        $errors[] = '해당하는 계정을 찾을 수 없습니다.';
                    } elseif ($found['role'] !== 'employee') {
                        $errors[] = '알바(employee) 계정만 연결할 수 있습니다.';
                        $found = null;
                    } else {
                        // 이미 이 매장에 소속된 경우 차단
                        $dup = DB::fetchOne(
                            "SELECT id FROM store_members WHERE store_id=? AND user_id=? AND employment_status IN ('active','on_leave')",
                            [$storeId, $found['id']]
                        );
                        if ($dup) {
                            $errors[] = '이미 이 매장에 소속된 계정입니다.';
                            $found = null;
                        }
                    }
                }
            } elseif ($action === 'link') {
                $userId     = (int)($_POST['user_id'] ?? 0);
                $hourlyWage = (int)($_POST['hourly_wage'] ?? 0);
                $user       = $userId ? User::find($userId) : null;

                if (!$user || $user['role'] !== 'employee') {
                    $errors[] = '유효하지 않은 계정입니다.';
                } elseif ($hourlyWage < 1) {
                    $errors[] = '시급을 입력하세요.';
                    $found = $user;
                } else {
                    // 중복 재확인
                    $dup = DB::fetchOne(
                        "SELECT id FROM store_members WHERE store_id=? AND user_id=? AND employment_status IN ('active','on_leave')",
                        [$storeId, $userId]
                    );
                    if ($dup) {
                        $errors[] = '이미 이 매장에 소속된 계정입니다.';
                    } else {
                        DB::query(
                            "INSERT INTO store_members
                               (store_id, user_id, name, account_status, employment_status,
                                hourly_wage, weekly_scheduled_hours, weekly_scheduled_days,
                                weekly_holiday_enabled, daily_break_minutes, employment_start_date,
                                is_active, joined_at, created_by_user_id, created_at, updated_at)
                             VALUES (?,?,?,'linked','active',?,?,?,?,?,?,1,NOW(),?,NOW(),NOW())",
                            [
                                $storeId,
                                $userId,
                                trim($_POST['name'] ?? $user['name']),
                                $hourlyWage,
                                (float)($_POST['weekly_contract_hours'] ?? 40),
                                (int)($_POST['weekly_contract_days']    ?? 5),
                                !empty($_POST['weekly_holiday_pay_enabled']) ? 1 : 0,
                                max(0, (int)($_POST['daily_break_minutes'] ?? 60)),
                                $_POST['hire_date'] ?: date('Y-m-d'),
                                Auth::id(),
                            ]
                        );
                        flash('success', h($user['name']) . ' 님의 계정이 연결됐습니다.');
                        redirect(url('members'));
                    }
                }
            }
        }

        render('members/link_account', [
            'title'  => '계정 ID로 연결',
            'errors' => $errors,
            'found'  => $found,
            'old'    => $_POST,
        ]);
    }

    public function create(): void
    {
        Auth::requireOwner();
        $storeId          = Auth::storeId();
        $errors           = [];
        $warnings         = [];
        $member           = [];
        $insuranceSetting = [];
        $settings         = Setting::get();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $post     = $_POST;
            $errors   = $this->validate($post);
            $warnings = $this->validateWarnings($post, $settings);

            if (!$errors) {
                $newMemberId = StoreMember::create(array_merge($post, [
                    'store_id'           => $storeId,
                    'user_id'            => null,
                    'account_status'     => 'no_account',
                    'employment_status'  => 'active',
                    'created_by_user_id' => Auth::id(),
                    'is_active'          => 1,
                ]));

                $checker  = new InsuranceEligibilityChecker();
                $judgment = $checker->checkAll($post);
                EmployeeInsuranceSetting::save($storeId, $newMemberId, $post, $judgment, Auth::id());

                // 노무 리스크 재탐지 (최저임금, 4대보험 상태)
                $savedMember = StoreMember::find($newMemberId, $storeId);
                if ($savedMember) {
                    LaborRiskEngine::detectForMember($savedMember, Auth::ownerId(), $storeId, $settings);
                }

                flash('success', '직원을 등록했습니다.');
                redirect(url('members'));
            }

            $member = $post;
        }

        render('members/form', [
            'title'            => '직원 등록',
            'action'           => 'create',
            'member'           => $member,
            'errors'           => $errors,
            'warnings'         => $warnings,
            'settings'         => $settings,
            'insuranceSetting' => $insuranceSetting,
        ]);
    }

    public function edit(): void
    {
        Auth::requireOwner();
        $storeId = Auth::storeId();
        $id      = (int)($_GET['id'] ?? 0);
        $member  = StoreMember::find($id, $storeId);

        if (!$member) {
            flash('error', '직원을 찾을 수 없습니다.');
            redirect(url('members'));
        }

        $insuranceSetting = EmployeeInsuranceSetting::findByMember($storeId, $id);

        // 계약 vs 실제 근무 비교 (편집 모드 경고용)
        $comparison = $this->buildComparison($id, $member);

        $errors   = [];
        $warnings = [];
        $settings = Setting::get();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $post     = $_POST;
            $errors   = $this->validate($post);
            $warnings = $this->validateWarnings($post, $settings);

            if (!$errors) {
                // 시급 변경 감지를 위해 수정 전 값 보관
                $before = StoreMember::find($id, $storeId);

                StoreMember::update($id, $storeId, array_merge($post, [
                    'store_id'          => $storeId,
                    'user_id'           => $member['user_id'],
                    'account_status'    => $member['account_status']    ?? ($member['user_id'] ? 'linked' : 'no_account'),
                    'employment_status' => $member['employment_status'] ?? 'active',
                ]));

                // 시급이 변경됐으면 이력 기록 (변경 시점부터 적용)
                $newWage = (int)($_POST['hourly_wage'] ?? 0);
                if ($before && (int)$before['hourly_wage'] !== $newWage) {
                    $effectiveFrom = trim($_POST['wage_effective_from'] ?? '') ?: date('Y-m-d');
                    $wageMemo      = trim($_POST['wage_change_memo'] ?? '');
                    WageHistory::record((int)$id, $storeId, Auth::ownerId(), $newWage, $effectiveFrom, $wageMemo);
                }

                $checker  = new InsuranceEligibilityChecker();
                $judgment = $checker->checkAll($post);
                EmployeeInsuranceSetting::save($storeId, $id, $post, $judgment, Auth::id());

                // 노무 리스크 재탐지 (최저임금, 4대보험 상태)
                $savedMember = StoreMember::find($id, $storeId);
                if ($savedMember) {
                    LaborRiskEngine::detectForMember($savedMember, Auth::ownerId(), $storeId, $settings);
                }

                flash('success', '직원 정보를 수정했습니다.');
                redirect(url('members'));
            }

            $member = array_merge($member, $post);
        }

        $pendingInvite = Invitation::forMember($id);
        $lastInviteLink   = $_SESSION['last_invite_link']   ?? null;
        $lastInviteMember = $_SESSION['last_invite_member'] ?? null;
        unset($_SESSION['last_invite_link'], $_SESSION['last_invite_member']);

        $wageHistory = WageHistory::forMember((int)$id);

        render('members/form', [
            'title'            => '직원 수정',
            'action'           => 'edit',
            'member'           => $member,
            'errors'           => $errors,
            'warnings'         => $warnings,
            'settings'         => $settings,
            'insuranceSetting' => $insuranceSetting,
            'comparison'       => $comparison,
            'pendingInvite'     => $pendingInvite,
            'lastInviteLink'    => $lastInviteLink,
            'lastInviteMember'  => $lastInviteMember,
            'wageHistory'       => $wageHistory,
        ]);
    }

    public function delete(): void
    {
        Auth::requireOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('members'));
        }
        verify_csrf();

        $storeId = Auth::storeId();
        $id      = (int)($_POST['id'] ?? 0);
        $member  = StoreMember::find($id, $storeId);

        if ($member) {
            StoreMember::delete($id, $storeId);
            flash('success', $member['name'] . '님을 삭제했습니다.');
        }
        redirect(url('members'));
    }

    private function buildComparison(int $memberId, array $member): array
    {
        $contractualWeekly  = (float)($member['weekly_scheduled_hours'] ?? 0);
        $contractualMonthly = round($contractualWeekly * 4.345, 1);

        $actualWeeks    = AttendanceLog::recentWeeklyActualHours($memberId, 4);
        $weeksWithData  = count(array_filter($actualWeeks, fn($w) => $w['actual_hours'] > 0));
        $totalActualH   = array_sum(array_column($actualWeeks, 'actual_hours'));
        $avgWeekly      = round($totalActualH / 4, 1);

        $monthSummary      = AttendanceLog::monthSummary($memberId, date('Y'), date('n'));
        $actualMonthHours  = round((int)$monthSummary['total_minutes'] / 60, 1);

        $hasEnoughData = $weeksWithData >= 2;

        return [
            'actual_weeks'        => $actualWeeks,
            'weeks_with_data'     => $weeksWithData,
            'actual_avg_weekly'   => $avgWeekly,
            'actual_month_hours'  => $actualMonthHours,
            'contractual_weekly'  => $contractualWeekly,
            'contractual_monthly' => $contractualMonthly,
            // Rule 6: 계약 < 15h 이지만 실제 >= 15h → 주휴/4대보험 리스크
            'warn_15h'            => $hasEnoughData && $contractualWeekly < 15 && $avgWeekly >= 15,
            // Rule 7: 계약 월 < 60h 이지만 실제 월 >= 60h → 4대보험 리스크
            'warn_60h'            => $weeksWithData >= 1 && $contractualMonthly < 60 && $actualMonthHours >= 60,
            // Rule 5: 반복적 불일치 (5시간/주 이상 차이)
            'warn_mismatch'       => $hasEnoughData && $contractualWeekly > 0 && abs($avgWeekly - $contractualWeekly) >= 5,
        ];
    }

    private function validate(array $post): array
    {
        $errors = [];
        if (empty(trim($post['name'] ?? ''))) {
            $errors[] = '이름을 입력하세요.';
        }
        if (empty($post['hourly_wage']) || (int)$post['hourly_wage'] < 1) {
            $errors[] = '시급을 입력하세요.';
        }
        return $errors;
    }

    /**
     * 저장을 막지 않는 경고 (노란 alert).
     * 최저임금 미달 시급은 저장은 허용하되 노무 리스크로 기록·표시한다.
     */
    private function validateWarnings(array $post, array $settings): array
    {
        $warnings   = [];
        $hourlyWage = (int)($post['hourly_wage'] ?? 0);
        $minWage    = (int)($settings['minimum_wage'] ?? 0);
        if ($minWage > 0 && $hourlyWage > 0 && $hourlyWage < $minWage) {
            $warnings[] = '시급이 최저임금(' . number_format($minWage) . '원)에 미달합니다. '
                . '저장은 가능하지만 노무 리스크가 기록됩니다.';
        }
        return $warnings;
    }
}
