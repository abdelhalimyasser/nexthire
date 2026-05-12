<?php 
declare(strict_types=1);

class DeptManagerController extends BaseController {

	public function index(): void {
		$this->requireRole(["hr_admin","dept_manager"]);
		$db = Database::getInstance();
		$pending = $db->query("SELECT jr.*, u.name as creator FROM job_requisitions jr JOIN users u ON jr.created_by=u.id WHERE jr.status='pending_approval' ORDER BY jr.created_at DESC")->fetchAll();
		$history = $db->query("SELECT jr.*, u.name as creator FROM job_requisitions jr JOIN users u ON jr.created_by=u.id WHERE jr.status IN ('live','cancelled') ORDER BY jr.updated_at DESC LIMIT 20")->fetchAll();

		$pageTitle = "Job Requisition Approvals";
		ob_start();
		?>
		<div class="flex justify-between items-center mb-6">
			<h1 class="text-2xl font-bold">Job Requisition Approvals</h1>
				<span class="px-3 py-1 rounded-full bg-amber-100 text-amber-700 text-sm font-medium"><?= count($pending) ?> Pending</span>
					</div>

					<?php if (empty($pending)): ?>
						<div class="bg-white rounded-xl border p-12 text-center mb-6">
							<svg class="w-12 h-12 mx-auto text-emerald-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
								<p class="text-slate-500">No pending requisitions. All caught up!</p>
									</div>
									<?php else: foreach($pending as $jr): ?>
											<div class="bg-white rounded-xl border p-6 mb-4 card-hover" id="jr-card-<?= $jr["id"] ?>">
												<div class="flex justify-between items-start mb-3">
													<div>
													<h3 class="font-semibold text-lg"><?= htmlspecialchars($jr["title"]) ?></h3>
														<p class="text-sm text-slate-500"><?= htmlspecialchars($jr["department"]) ?> &middot;
		<?= $jr["level"] ?> &middot;
		Submitted by <?= htmlspecialchars($jr["creator"]) ?></p>
		</div>
		<span class="px-3 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold">Pending Approval</span>
			</div>
			<p class="text-sm text-slate-600 mb-4 line-clamp-2"><?= htmlspecialchars(substr($jr["description"] ?? "", 0, 200)) ?>...</p>
				<div id="jr-actions-<?= $jr["id"] ?>" class="flex gap-3 items-center">
					<button onclick="approveJr(<?= $jr["id"] ?>)" class="px-5 py-2 bg-emerald-600 text-white text-sm rounded-lg font-medium hover:bg-emerald-700 transition" id="btn-approve-<?= $jr["id"] ?>">Approve</button>
						<button onclick="showRejectForm(<?= $jr["id"] ?>)" class="px-5 py-2 bg-red-600 text-white text-sm rounded-lg font-medium hover:bg-red-700 transition">Reject</button>
							<a href="index.php?page=hr/jobs&action=view&id=<?= $jr["id"] ?>" class="text-indigo-600 text-sm hover:underline">View Details</a>
								</div>
								<div id="reject-form-<?= $jr["id"] ?>" class="hidden mt-4 border-t pt-4">
									<label class="block text-sm font-medium text-slate-700 mb-2">Reason for rejection (required)</label>
											<textarea id="reject-reason-<?= $jr["id"] ?>" rows="3" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="Explain why this requisition is being rejected..."></textarea>
												<div class="flex gap-2 mt-2">
													<button onclick="rejectJr(<?= $jr["id"] ?>)" class="px-4 py-2 bg-red-600 text-white text-sm rounded-lg">Confirm Rejection</button>
														<button onclick="hideRejectForm(<?= $jr["id"] ?>)" class="px-4 py-2 bg-slate-200 text-slate-700 text-sm rounded-lg">Cancel</button>
															</div>
															</div>
															</div>
															<?php endforeach;
		endif;
		?>

		<?php if (!empty($history)): ?>
			<h2 class="text-lg font-semibold mt-8 mb-4">Approval History</h2>
				<div class="bg-white rounded-xl border overflow-hidden">
					<table class="w-full text-sm">
						<thead><tr class="bg-slate-50 border-b text-left text-slate-500">
							<th class="p-4">Requisition</th><th class="p-4">Department</th>
									<th class="p-4">Decision</th><th class="p-4">Decided By</th><th class="p-4">Date</th>
												</tr></thead><tbody>
												<?php foreach($history as $h): ?>
													<tr class="border-b hover:bg-slate-50">
														<td class="p-4 font-medium"><?= htmlspecialchars($h["title"]) ?></td>
															<td class="p-4"><?= htmlspecialchars($h["department"]) ?></td>
																<td class="p-4"><span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= $h["status"]==="live"?"bg-emerald-100 text-emerald-700":"bg-red-100 text-red-700" ?>"><?= $h["status"]==="live"?"Approved":"Rejected" ?></span></td>
																		<td class="p-4"><?= htmlspecialchars($h["creator"]) ?></td>
																			<td class="p-4 text-slate-500"><?= date("M j, Y", strtotime($h["updated_at"])) ?></td>
																				</tr>
																				<?php endforeach;
		?>
        
		</tbody></table>
		</div>
		<?php 
        endif;
		?>

		<script>
		const CSRF = <?= json_encode($this->generateCsrf()) ?>;
		async function approveJr(id) {
			const btn = document.getElementById("btn-approve-"+id);
			btn.disabled = true;
			btn.textContent = "Approving...";
			const r = await fetch("index.php?page=dept_manager&action=approve", {
method:"POST", headers:{"Content-Type":"application/x-www-form-urlencoded"},
body:"id="+id+"&<?= CSRF_TOKEN_NAME ?>="+CSRF
			});
			const d = await r.json();
			if (d.success) {
				const card = document.getElementById("jr-card-"+id);
				card.innerHTML = `<div class="flex items-center gap-3 text-emerald-700"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg><strong>$ {d.title}</strong> has been approved and is now live.</div>`;
				card.classList.add("border-emerald-200","bg-emerald-50");
			} else {
				btn.disabled=false;
				btn.textContent="Approve";
				alert(d.error||"Failed");
			}
		}
		function showRejectForm(id) {
			document.getElementById("reject-form-"+id).classList.remove("hidden");
		}
		function hideRejectForm(id) {
			document.getElementById("reject-form-"+id).classList.add("hidden");
		}
		async function rejectJr(id) {
			const reason = document.getElementById("reject-reason-"+id).value.trim();
			if (!reason) {
				alert("Please provide a rejection reason.");
				return;
			}
			const r = await fetch("index.php?page=dept_manager&action=reject", {
method:"POST", headers:{"Content-Type":"application/x-www-form-urlencoded"},
body:"id="+id+"&reason="+encodeURIComponent(reason)+"&<?= CSRF_TOKEN_NAME ?>="+CSRF
			});
			const d = await r.json();
			if (d.success) {
				const card = document.getElementById("jr-card-"+id);
				card.innerHTML = `<div class="flex items-center gap-3 text-red-700"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg><strong>$ {d.title}</strong> has been rejected.</div>`;
				card.classList.add("border-red-200","bg-red-50");
			} else {
				alert(d.error||"Failed");
			}
		}
		</script>
		<?php $content = ob_get_clean();
		$this->renderLayout($content, compact("pageTitle"));
	}

	public function approve(): void {
		$this->requireRole(["hr_admin","dept_manager"]);
		if (!$this->validateCsrf()) {
			$this->jsonResponse(["error"=>"Invalid token"],403);
			return;
		}
		$id = $this->getIntInput("id");
		$db = Database::getInstance();
		$jr = (new JobRequisitionModel())->findById($id);
		if (!$jr || $jr["status"] !== "pending_approval") {
			$this->jsonResponse(["error"=>"Not found or not pending"],404);
			return;
		}

		$db->prepare("UPDATE job_requisitions SET status='live', updated_at=NOW() WHERE id=:id")
		->execute(["id"=>$id]);
		AuditLogger::getInstance()->log((int)$this->currentUser["id"],"job_requisition",$id,"approved",[],["approver"=>$this->currentUser["name"]]);

		// Notify HR Admins
		$hrs = (new UserModel())->findByRole("hr_admin");
		foreach ($hrs as $hr) {
			EmailService::getInstance()->sendTemplate($hr["email"],"Job Requisition Approved","requisition_approved",
			        ["title"=>$jr["title"],"approver"=>$this->currentUser["name"],"comments"=>""]);
		}
		EventBus::getInstance()->publish("JobApprovedEvent",["job_id"=>$id]);
		$this->jsonResponse(["success"=>true,"title"=>$jr["title"]]);
	}

	public function reject(): void {
		$this->requireRole(["hr_admin","dept_manager"]);
		if (!$this->validateCsrf()) {
			$this->jsonResponse(["error"=>"Invalid token"],403);
			return;
		}
		$id = $this->getIntInput("id");
		$reason = $this->getInput("reason");
		if (empty($reason)) {
			$this->jsonResponse(["error"=>"Reason required"],400);
			return;
		}
		$jr = (new JobRequisitionModel())->findById($id);
		if (!$jr) {
			$this->jsonResponse(["error"=>"Not found"],404);
			return;
		}

		Database::getInstance()->prepare("UPDATE job_requisitions SET status='cancelled', updated_at=NOW() WHERE id=:id")
		->execute(["id"=>$id]);
		AuditLogger::getInstance()->log((int)$this->currentUser["id"],"job_requisition",$id,"rejected",[],["reason"=>$reason]);

		$hrs = (new UserModel())->findByRole("hr_admin");
		foreach ($hrs as $hr) {
			EmailService::getInstance()->sendTemplate($hr["email"],"Job Requisition Rejected","requisition_rejected",
			        ["title"=>$jr["title"],"approver"=>$this->currentUser["name"],"reason"=>$reason]);
		}
		$this->jsonResponse(["success"=>true,"title"=>$jr["title"]]);
	}
}