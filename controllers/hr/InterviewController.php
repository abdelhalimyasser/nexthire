<?php 
declare(strict_types=1);

class InterviewController extends BaseController {

	/** List all interview panels for HR Admin, or candidate's own interviews */
	public function index(): void {
		$this->requireAuth();
		$role = $this->currentUser["role"];
		$db = Database::getInstance();

		if ($role === "candidate") {
			// Candidate sees their own interviews
			$stmt = $db->prepare("
			                     SELECT ip.*, jr.title as job_title, ip.scheduled_at, ip.duration_minutes, ip.status
			                     FROM interview_panels ip
			                     JOIN applications a ON ip.application_id = a.id
			                     JOIN job_requisitions jr ON ip.job_id = jr.id
			                     WHERE a.candidate_id = :cid
			                     ORDER BY ip.scheduled_at DESC
			                     ");
			                     $stmt->execute(["cid" => $this->currentUser["id"]]);
			                     $panels = $stmt->fetchAll();

			                     $pageTitle = "My Interviews";
			                     ob_start();
			                     ?>
			                     <h1 class="text-2xl font-bold mb-6">My Interviews</h1>
			                     <?php if (empty($panels)): ?>
			                     <div class="bg-white rounded-xl border p-8 text-center">
			                     <svg class="w-16 h-16 mx-auto text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
			                     <p class="text-slate-500">No interviews scheduled yet.</p>
			                     <p class="text-sm text-slate-400 mt-1">When your application reaches the interview stage, your interview will appear here.</p>
			                     </div>
			                     <?php else: ?>
				                     <div class="space-y-3">
				                     <?php foreach ($panels as $p): ?>
					                     <div class="bg-white rounded-xl border p-5 flex justify-between items-center card-hover">
					                     <div>
					                     <h3 class="font-semibold"><?= htmlspecialchars($p["job_title"]) ?></h3>
					                     <p class="text-sm text-slate-500"><?= $p["scheduled_at"] ?> &middot; <?= $p["duration_minutes"] ?> min</p>
					                     <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-semibold <?= $p["status"]==="scheduled" ? "bg-blue-100 text-blue-700" : ($p["status"]==="active" ? "bg-emerald-100 text-emerald-700" : "bg-slate-100 text-slate-600") ?>">
					                     <?= ucfirst($p["status"]) ?>
					                     </span>
					                     </div>
					                     <?php if (in_array($p["status"], ["scheduled","active"])): ?>
						                     <a href="index.php?page=candidate/interview&action=join&id=<?= $p["id"] ?>"
						                     class="px-5 py-2.5 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition font-semibold">
						                     Join Interview
						                     </a>
						                     <?php else: ?>
							                     <span class="px-4 py-2 bg-slate-100 text-slate-500 text-sm rounded-lg">Completed</span>
							                     <?php endif; ?>
							                     </div>
							                     <?php endforeach; ?>
							                     </div>
							                     <?php endif; ?>
							                     <?php
							                     $content = ob_get_clean();
							                     $this->renderLayout($content, compact("pageTitle"));
							                     return;
						}

	// HR Admin: see all panels
	$this->requireRole("hr_admin");
		$stmt = $db->prepare("
		                     SELECT ip.*, jr.title as job_title, u.name as candidate_name, a.stage as app_stage,
		                     (SELECT COUNT(*) FROM panel_members pm WHERE pm.panel_id = ip.id) as member_count
		                     FROM interview_panels ip
		                     JOIN job_requisitions jr ON ip.job_id = jr.id
		                     JOIN applications a ON ip.application_id = a.id
		                     JOIN users u ON a.candidate_id = u.id
		                     ORDER BY FIELD(ip.status,'active','scheduled','completed','cancelled'), ip.scheduled_at ASC
		                     ");
		                     $stmt->execute();
		                     $panels = $stmt->fetchAll();

		                     // Also get applications at interview stage WITHOUT a panel (orphans)
		                     $orphans = $db->query("
		                             SELECT a.id as app_id, a.job_id, u.name as candidate_name, jr.title as job_title
		                             FROM applications a
		                             JOIN users u ON a.candidate_id = u.id
		                             JOIN job_requisitions jr ON a.job_id = jr.id
		                             LEFT JOIN interview_panels ip ON ip.application_id = a.id
		                             WHERE a.stage = 'interview' AND ip.id IS NULL
		                             ")->fetchAll();

		                             // Get available interviewers for scheduling
		                             $interviewers = (new UserModel())->findByRole("interviewer");

		                             $pageTitle = "Interview Management";
		                             ob_start();
		                             ?>
		                             <div class="flex justify-between items-center mb-6">
		                             <h1 class="text-2xl font-bold">Interview Management</h1>
		                             <div class="flex gap-3">
		                             <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-sm font-medium"><?= count(array_filter($panels, fn($p) => $p["status"]==="scheduled")) ?> Scheduled</span>
		                             <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-sm font-medium"><?= count(array_filter($panels, fn($p) => $p["status"]==="active")) ?> Active</span>
		                             </div>
		                             </div>

		                             <?php if (!empty($orphans)): ?>
		                             <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 mb-6">
		                             <h3 class="font-semibold text-amber-800 mb-3">⚠ Candidates at Interview Stage Without Panels</h3>
		                             <div class="space-y-2">
		                             <?php foreach ($orphans as $o): ?>
			                             <div class="flex justify-between items-center bg-white rounded-lg p-3 border border-amber-100">
			                             <div>
			                             <p class="font-medium text-sm"><?= htmlspecialchars($o["candidate_name"]) ?></p>
			                             <p class="text-xs text-slate-500"><?= htmlspecialchars($o["job_title"]) ?> &middot; App #<?= $o["app_id"] ?></p>
			                             </div>
			                             <a href="index.php?page=hr/interviews&action=schedule&application_id=<?= $o["app_id"] ?>"
			                             class="px-4 py-2 bg-amber-600 text-white text-sm rounded-lg hover:bg-amber-700 transition font-medium">
			                             Schedule Interview
			                             </a>
			                             </div>
			                             <?php endforeach; ?>
			                             </div>
			                             </div>
			                             <?php endif; ?>

			                             <div class="bg-white rounded-xl border overflow-hidden">
			                             <table class="w-full text-sm">
			                             <thead><tr class="border-b text-left text-slate-500 bg-slate-50">
			                             <th class="p-4">Panel</th><th class="p-4">Job</th><th class="p-4">Candidate</th>
			                             <th class="p-4">Scheduled</th><th class="p-4">Duration</th><th class="p-4">Members</th>
			                             <th class="p-4">Status</th><th class="p-4">Actions</th>
			                             </tr></thead>
			                             <tbody>
			                             <?php foreach ($panels as $p): ?>
				                             <tr class="border-b hover:bg-slate-50">
				                             <td class="p-4 font-medium">#<?= $p["id"] ?></td>
				                             <td class="p-4"><?= htmlspecialchars($p["job_title"]) ?></td>
				                             <td class="p-4"><?= htmlspecialchars($p["candidate_name"]) ?></td>
				                             <td class="p-4 text-xs"><?= $p["scheduled_at"] ?></td>
				                             <td class="p-4"><?= (int)$p["duration_minutes"] + (int)$p["extended_by_minutes"] ?>min</td>
				                             <td class="p-4"><?= $p["member_count"] ?></td>
				                             <td class="p-4">
				                             <span class="px-2 py-0.5 rounded-full text-xs font-semibold
				                             <?= $p["status"]==="scheduled" ? "bg-blue-100 text-blue-700" :
				                             ($p["status"]==="active" ? "bg-emerald-100 text-emerald-700" :
				                              ($p["status"]==="completed" ? "bg-slate-100 text-slate-600" : "bg-red-100 text-red-700")) ?>">
				                             <?= ucfirst($p["status"]) ?>
				                             </span>
				                             </td>
				                             <td class="p-4">
				                             <div class="flex gap-2">
				                             <?php if (in_array($p["status"], ["scheduled","active"])): ?>
					                             <a href="index.php?page=interviewer/live&action=join&id=<?= $p["id"] ?>"
					                             class="px-3 py-1 bg-indigo-600 text-white text-xs rounded-lg hover:bg-indigo-700">Join</a>
					                             <?php endif; ?>
					                             <a href="index.php?page=hr/interviews&action=manage&id=<?= $p["id"] ?>"
					                             class="px-3 py-1 bg-slate-200 text-slate-700 text-xs rounded-lg hover:bg-slate-300">Manage</a>
					                             </div>
					                             </td>
					                             </tr>
					                             <?php endforeach; ?>
					                             <?php if (empty($panels)): ?>
						                             <tr><td colspan="8" class="p-8 text-center text-slate-400">No interview panels created yet.</td></tr>
						                             <?php endif; ?>
						                             </tbody>
						                             </table>
						                             </div>
						                             <?php
						                             $content = ob_get_clean();
						                             $this->renderLayout($content, compact("pageTitle"));
					}

                     /** Schedule form for a specific application */
                     public function schedule(): void {
		$this->requireRole("hr_admin");
		$appId = $this->getIntInput("application_id");
		$db = Database::getInstance();

		$app = (new ApplicationModel())->getWithDetails($appId);
		if (!$app) {
			$this->setFlash("error", "Application not found.");
			$this->redirect("index.php?page=hr/interviews");
			return;
		}

		$interviewers = (new UserModel())->findByRole("interviewer");

		$pageTitle = "Schedule Interview";
		ob_start();
		?>
		<div class="max-w-2xl mx-auto">
			<h1 class="text-2xl font-bold mb-6">Schedule Interview</h1>
				<div class="bg-white rounded-xl border p-6 mb-6">
					<h3 class="font-semibold mb-1"><?= htmlspecialchars($app["candidate_name"]) ?></h3>
						<p class="text-sm text-slate-500"><?= htmlspecialchars($app["job_title"]) ?> &middot; <?= $app["department"] ?> &middot; App #<?= $appId ?></p>
		</div>
		<form method="POST" action="index.php?page=hr/interviews&action=save" class="space-y-4">
			<?= $this->csrfField() ?>
			<input type="hidden" name="application_id" value="<?= $appId ?>">
			<input type="hidden" name="job_id" value="<?= $app["job_id"] ?>">

			<div class="bg-white rounded-xl border p-6 space-y-4">
				<div>
				<label class="block text-sm font-semibold text-slate-700 mb-1">Date & Time</label>
					<input type="datetime-local" name="scheduled_at" required
					value="<?= date('Y-m-d\TH:i', strtotime('+2 days')) ?>"
					class="w-full px-3 py-2 border rounded-lg text-sm">
						</div>
						<div>
						<label class="block text-sm font-semibold text-slate-700 mb-1">Duration (minutes)</label>
							<select name="duration_minutes" class="w-full px-3 py-2 border rounded-lg text-sm">
								<option value="30">30 min</option>
								<option value="45">45 min</option>
								<option value="60" selected>60 min</option>
								<option value="90">90 min</option>
								<option value="120">120 min</option>
								</select>
								</div>
								<div>
								<label class="block text-sm font-semibold text-slate-700 mb-1">Coding Language</label>
									<select name="coding_language" class="w-full px-3 py-2 border rounded-lg text-sm">
										<?php foreach (CODING_LANGUAGE_LABELS as $k => $l): ?>
										<option value="<?= $k ?>"><?= $l ?></option>
										<?php endforeach; ?>
		</select>
		</div>
		<div>
		<label class="block text-sm font-semibold text-slate-700 mb-2">Assign Interviewers</label>
			<div class="space-y-2 max-h-48 overflow-y-auto">
				<?php foreach ($interviewers as $iv): ?>
					<label class="flex items-center gap-3 p-2 rounded-lg hover:bg-slate-50 cursor-pointer">
						<input type="checkbox" name="interviewers[]" value="<?= $iv["id"] ?>"
						class="rounded border-slate-300 text-indigo-600">
							<div>
							<p class="text-sm font-medium"><?= htmlspecialchars($iv["name"]) ?></p>
								<p class="text-xs text-slate-400"><?= $iv["department"] ?> &middot; <?= ucfirst($iv["seniority"] ?? "") ?></p>
		</div>
		</label>
		<?php endforeach; ?>
		</div>
		</div>
		<div>
		<label class="block text-sm font-semibold text-slate-700 mb-1">Notes</label>
			<textarea name="notes" rows="2" class="w-full px-3 py-2 border rounded-lg text-sm"
				placeholder="Interview notes or special instructions..."></textarea>
				</div>
				</div>
				<button type="submit" class="w-full py-3 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 transition">
					Schedule Interview
					</button>
					</form>
					</div>
					<?php
					$content = ob_get_clean();
		$this->renderLayout($content, compact("pageTitle"));
	}

	/** Save a new interview panel */
	public function save(): void {
		$this->requireRole("hr_admin");
		if (!$this->validateCsrf()) {
			$this->setFlash("error", "Security token expired.");
			$this->redirect("index.php?page=hr/interviews");
			return;
		}

		$appId = (int)($_POST["application_id"] ?? 0);
		$jobId = (int)($_POST["job_id"] ?? 0);
		$scheduledAt = $_POST["scheduled_at"] ?? "";
		$duration = (int)($_POST["duration_minutes"] ?? 60);
		$language = $_POST["coding_language"] ?? "javascript";
		$notes = trim($_POST["notes"] ?? "");
		$interviewerIds = $_POST["interviewers"] ?? [];

		if (!$appId || !$jobId || !$scheduledAt) {
			$this->setFlash("error", "Missing required fields.");
			$this->redirect("index.php?page=hr/interviews&action=schedule&application_id=$appId");
			return;
		}

		$db = Database::getInstance();
		$candidateToken = bin2hex(random_bytes(32));

		// Create panel
		$db->prepare("
		             INSERT INTO interview_panels (job_id, application_id, scheduled_at, timezone, duration_minutes, status, candidate_token, coding_language, notes)
		             VALUES (:jid, :aid, :sat, 'UTC', :dur, 'scheduled', :tok, :lang, :notes)
		             ")->execute([
		             "jid" => $jobId, "aid" => $appId, "sat" => $scheduledAt,
		             "dur" => $duration, "tok" => $candidateToken, "lang" => $language, "notes" => $notes
		            ]);
		$panelId = (int)$db->lastInsertId();

		// Add HR admin as panel member
		$db->prepare("INSERT INTO panel_members (panel_id, user_id, role) VALUES (:pid, :uid, 'hr')")
		->execute(["pid" => $panelId, "uid" => $this->currentUser["id"]]);

		// Add selected interviewers
		$first = true;
		foreach ($interviewerIds as $ivId) {
		$role = $first ? "lead" : "technical";
		$db->prepare("INSERT IGNORE INTO panel_members (panel_id, user_id, role) VALUES (:pid, :uid, :r)")
			->execute(["pid" => $panelId, "uid" => (int)$ivId, "r" => $role]);
			$first = false;
		}

		AuditLogger::getInstance()->log((int)$this->currentUser["id"], "interview_panel", $panelId, "created", [], ["application_id" => $appId]);
		$this->setFlash("success", "Interview scheduled for " . $scheduledAt . " (Panel #$panelId)");
		$this->redirect("index.php?page=hr/interviews");
	}

	/** Manage a specific panel — view/edit members, reschedule */
	public function manage(): void {
		$this->requireRole("hr_admin");
		$panelId = $this->getIntInput("id");
		$db = Database::getInstance();

		$stmt = $db->prepare("
		                     SELECT ip.*, jr.title as job_title, u.name as candidate_name, u.email as candidate_email
		                     FROM interview_panels ip
		                     JOIN job_requisitions jr ON ip.job_id = jr.id
		                     JOIN applications a ON ip.application_id = a.id
		                     JOIN users u ON a.candidate_id = u.id
		                     WHERE ip.id = :id
		                     ");
		                     $stmt->execute(["id" => $panelId]);
		                     $panel = $stmt->fetch();

		if (!$panel) {
		$this->setFlash("error", "Panel not found.");
			$this->redirect("index.php?page=hr/interviews");
			return;
		}

		$members = (new InterviewModel())->getMembers($panelId);
		$interviewers = (new UserModel())->findByRole("interviewer");
		$memberIds = array_column($members, "user_id");

		$pageTitle = "Manage Panel #$panelId";
		ob_start();
		?>
		<div class="max-w-3xl mx-auto">
		<div class="flex justify-between items-center mb-6">
		<h1 class="text-2xl font-bold">Panel #<?= $panelId ?></h1>
		<a href="index.php?page=hr/interviews" class="text-sm text-indigo-600 hover:underline">&larr; Back to Interviews</a>
		</div>

		<div class="bg-white rounded-xl border p-6 mb-6">
		<h3 class="font-semibold"><?= htmlspecialchars($panel["job_title"]) ?></h3>
		<p class="text-sm text-slate-500">Candidate: <?= htmlspecialchars($panel["candidate_name"]) ?> (<?= htmlspecialchars($panel["candidate_email"]) ?>)</p>
		<div class="grid grid-cols-3 gap-4 mt-4">
		<div><p class="text-xs text-slate-400">Scheduled</p><p class="font-medium text-sm"><?= $panel["scheduled_at"] ?></p></div>
		<div><p class="text-xs text-slate-400">Duration</p><p class="font-medium text-sm"><?= (int)$panel["duration_minutes"] + (int)$panel["extended_by_minutes"] ?> min</p></div>
		<div><p class="text-xs text-slate-400">Status</p><p class="font-medium text-sm"><?= ucfirst($panel["status"]) ?></p></div>
		</div>
		<?php if (in_array($panel["status"], ["scheduled","active"])): ?>
		<div class="mt-4">
		<a href="index.php?page=interviewer/live&action=join&id=<?= $panelId ?>"
		class="inline-block px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">
		Join Interview Room
		</a>
		</div>
		<?php endif; ?>
		</div>

		<!-- Reschedule Form -->
		<?php if ($panel["status"] === "scheduled"): ?>
			<form method="POST" action="index.php?page=hr/interviews&action=reschedule" class="bg-white rounded-xl border p-6 mb-6">
			<?= $this->csrfField() ?>
			<input type="hidden" name="panel_id" value="<?= $panelId ?>">
			<h3 class="font-semibold mb-3">Reschedule</h3>
			<div class="flex gap-3">
			<input type="datetime-local" name="scheduled_at" value="<?= date('Y-m-d\TH:i', strtotime($panel["scheduled_at"])) ?>"
			class="flex-1 px-3 py-2 border rounded-lg text-sm">
			<button type="submit" class="px-5 py-2 bg-amber-600 text-white rounded-lg text-sm font-semibold hover:bg-amber-700 transition">Update</button>
			</div>
			</form>
			<?php endif; ?>

			<!-- Panel Members -->
			<div class="bg-white rounded-xl border p-6 mb-6">
			<h3 class="font-semibold mb-4">Panel Members (<?= count($members) ?>)</h3>
			<div class="space-y-2 mb-4">
			<?php foreach ($members as $m): ?>
				<div class="flex justify-between items-center p-3 bg-slate-50 rounded-lg">
				<div>
				<p class="font-medium text-sm"><?= htmlspecialchars($m["name"]) ?></p>
				<p class="text-xs text-slate-400"><?= htmlspecialchars($m["email"]) ?> &middot; <?= ucfirst($m["role"]) ?></p>
				</div>
				</div>
				<?php endforeach; ?>
				<?php if (empty($members)): ?>
					<p class="text-sm text-slate-400 text-center py-3">No members assigned yet.</p>
					<?php endif; ?>
					</div>

					<?php if ($panel["status"] === "scheduled"): ?>
						<form method="POST" action="index.php?page=hr/interviews&action=add_member">
						<?= $this->csrfField() ?>
						<input type="hidden" name="panel_id" value="<?= $panelId ?>">
						<h4 class="text-sm font-semibold text-slate-600 mb-2">Add Interviewer</h4>
						<div class="flex gap-2">
						<select name="user_id" class="flex-1 px-3 py-2 border rounded-lg text-sm">
						<?php foreach ($interviewers as $iv):
							if (in_array($iv["id"], $memberIds)) continue; ?>
								<option value="<?= $iv["id"] ?>"><?= htmlspecialchars($iv["name"]) ?> (<?= $iv["department"] ?>)</option>
								<?php endforeach; ?>
								</select>
								<select name="role" class="px-3 py-2 border rounded-lg text-sm">
								<option value="technical">Technical</option>
								<option value="lead">Lead</option>
								</select>
								<button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-sm rounded-lg hover:bg-emerald-700">Add</button>
								</div>
								</form>
								<?php endif; ?>
								</div>
								</div>
								<?php
								$content = ob_get_clean();
								$this->renderLayout($content, compact("pageTitle"));
							}

/** POST: Reschedule a panel */
public function reschedule(): void {
		$this->requireRole("hr_admin");
		if (!$this->validateCsrf()) {
			$this->setFlash("error","CSRF error");
			$this->redirect("index.php?page=hr/interviews");
			return;
		}
		$panelId = (int)($_POST["panel_id"] ?? 0);
		$scheduledAt = $_POST["scheduled_at"] ?? "";
		if ($panelId && $scheduledAt) {
			Database::getInstance()->prepare("UPDATE interview_panels SET scheduled_at=:s WHERE id=:id AND status='scheduled'")
			->execute(["s" => $scheduledAt, "id" => $panelId]);
			$this->setFlash("success", "Panel #$panelId rescheduled.");
		}
		$this->redirect("index.php?page=hr/interviews&action=manage&id=$panelId");
	}

	/** POST: Add a member to a panel */
	public function add_member(): void {
		$this->requireRole("hr_admin");
		if (!$this->validateCsrf()) {
			$this->setFlash("error","CSRF error");
			$this->redirect("index.php?page=hr/interviews");
			return;
		}
		$panelId = (int)($_POST["panel_id"] ?? 0);
		$userId = (int)($_POST["user_id"] ?? 0);
		$role = $_POST["role"] ?? "technical";
		if ($panelId && $userId) {
			(new InterviewModel())->addMember($panelId, $userId, $role);
			$this->setFlash("success", "Member added to Panel #$panelId.");
		}
		$this->redirect("index.php?page=hr/interviews&action=manage&id=$panelId");
	}
}
