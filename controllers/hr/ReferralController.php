<?php 
declare(strict_types=1);

class ReferralController extends BaseController {
	public function index(): void {
		$this->requireRole(["hr_admin","interviewer","dept_manager","shadow"]);
		$db = Database::getInstance();
		$myRefs = $db->prepare("SELECT ri.*, jr.title as job_title FROM referral_invites ri LEFT JOIN job_requisitions jr ON ri.job_id=jr.id WHERE ri.referred_by=:uid ORDER BY ri.created_at DESC");
		$myRefs->execute(["uid"=>$this->currentUser["id"]]);
		$myRefs = $myRefs->fetchAll();
		$jobs = $db->query("SELECT id,title,department FROM job_requisitions WHERE status='live' ORDER BY title")->fetchAll();
		$pageTitle = "Referrals";
		ob_start();
		?>
		<div class="flex justify-between items-center mb-6">
			<h1 class="text-2xl font-bold">Referral System</h1>
				<button onclick="document.getElementById('referralModal').classList.remove('hidden')" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg font-medium">Refer a Candidate</button>
					</div>

					<!-- Modal -->
					<div id="referralModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
						<div class="bg-white rounded-xl p-6 max-w-md w-full shadow-2xl">
							<div class="flex justify-between items-center mb-4"><h3 class="font-bold text-lg">Refer a Candidate</h3><button onclick="document.getElementById('referralModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">&#x2715;
		</button></div>
		<form id="referralForm" class="space-y-3">
			<?= $this->csrfField() ?>
			<div><label class="block text-sm font-medium mb-1">Candidate Name <span class="text-red-500">*</span></label><input name="candidate_name" required class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="Full name"></div>
						<div><label class="block text-sm font-medium mb-1">Candidate Email <span class="text-red-500">*</span></label><input type="email" name="candidate_email" required class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="candidate@email.com"></div>
									<div><label class="block text-sm font-medium mb-1">Position (optional)</label>
										<select name="job_id" class="w-full px-3 py-2 border rounded-lg text-sm"><option value="">-- Any open position --</option>
											<?php foreach($jobs as $j): ?><option value="<?= $j["id"] ?>"><?= htmlspecialchars($j["title"]) ?> (<?= $j["department"] ?>)</option><?php endforeach;
		?>
		</select>
		</div>
		<button type="submit" id="refBtn" class="w-full py-2 bg-indigo-600 text-white rounded-lg font-medium text-sm">Send Referral Invite</button>
			<p id="refResult" class="text-center text-sm hidden"></p>
				</form>
				</div>
				</div>

				<div class="bg-white rounded-xl border overflow-hidden">
					<table class="w-full text-sm"><thead><tr class="bg-slate-50 border-b text-left text-slate-500"><th class="p-4">Candidate</th><th class="p-4">Email</th><th class="p-4">Position</th><th class="p-4">Status</th><th class="p-4">Sent</th></tr></thead><tbody>
												<?php foreach($myRefs as $r): $sc=["pending"=>"bg-amber-100 text-amber-700","registered"=>"bg-emerald-100 text-emerald-700","expired"=>"bg-red-100 text-red-700"];
		?>
		<tr class="border-b hover:bg-slate-50"><td class="p-4 font-medium"><?= htmlspecialchars($r["candidate_name"]) ?></td><td class="p-4 text-slate-500"><?= htmlspecialchars($r["candidate_email"]) ?></td><td class="p-4"><?= htmlspecialchars($r["job_title"] ?? "Any") ?></td><td class="p-4"><span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= $sc[$r["status"]] ?>"><?= ucfirst($r["status"]) ?></span></td><td class="p-4 text-slate-400"><?= date("M j",strtotime($r["created_at"])) ?></td></tr>
									<?php endforeach;
		if(empty($myRefs)): ?><tr><td colspan="5" class="p-8 text-center text-slate-400">No referrals yet</td></tr><?php endif;
		?>
		</tbody></table>
		</div>
		<script>
		document.getElementById("referralForm").addEventListener("submit", async function(e) {
			e.preventDefault();
			const btn=document.getElementById("refBtn");
			btn.disabled=true;
			btn.textContent="Sending...";
const r=await fetch("index.php?page=hr/referrals&action=create", {method:"POST",body:new URLSearchParams(new FormData(this))});
			const d=await r.json();
			const res=document.getElementById("refResult");
			res.classList.remove("hidden");
			if(d.success) {
				res.className="text-center text-sm text-emerald-600";
				res.textContent="Invite sent to "+d.email+"!";
				btn.textContent="Sent";
			}
			else {
				res.className="text-center text-sm text-red-600";
				res.textContent=d.error||"Failed";
				btn.disabled=false;
				btn.textContent="Send Referral Invite";
			}
		});
		</script>
		<?php $content = ob_get_clean();
		$this->renderLayout($content, compact("pageTitle"));
	}

	public function create(): void {
		$this->requireRole(["hr_admin","interviewer","dept_manager","shadow"]);
		if (!$this->validateCsrf()) {
			$this->jsonResponse(["error"=>"Invalid token"],403);
			return;
		}
		$name  = $this->getInput("candidate_name");
		$email = filter_input(INPUT_POST,"candidate_email",FILTER_SANITIZE_EMAIL);
		$jobId = $this->getIntInput("job_id") ?: null;
		if (empty($name)||!filter_var($email,FILTER_VALIDATE_EMAIL)) {
			$this->jsonResponse(["error"=>"Name and valid email required"]);
			return;
		}
		$token = bin2hex(random_bytes(32));
		$db = Database::getInstance();
		$db->prepare("INSERT INTO referral_invites (referred_by,candidate_email,candidate_name,job_id,token,expires_at) VALUES(:rb,:ce,:cn,:jid,:t,DATE_ADD(NOW(),INTERVAL 7 DAY))")
		->execute(["rb"=>$this->currentUser["id"],"ce"=>$email,"cn"=>$name,"jid"=>$jobId,"t"=>$token]);
		$link = BASE_URL . "/index.php?page=auth&action=referral&token=" . $token;
		$jobTitle = "";
		if ($jobId) {
			$j=$db->prepare("SELECT title FROM job_requisitions WHERE id=:id");
			$j->execute(["id"=>$jobId]);
			$jd=$j->fetch();
			$jobTitle=$jd["title"]??"";
		}
		EmailService::getInstance()->sendTemplate($email,"You have been referred to NextHire!","referral_invite",["name"=>$name,"referrer"=>$this->currentUser["name"],"job_title"=>$jobTitle,"link"=>$link]);
		// Notify HR Admin
		foreach((new UserModel())->findByRole("hr_admin") as $hr) {
			EmailService::getInstance()->sendTemplate($hr["email"],"New Candidate Referral","job_created",["title"=>"Referral: $name","department"=>$this->currentUser["name"]." referred $email","level"=>""]);
		}
		AuditLogger::getInstance()->log((int)$this->currentUser["id"],"referral_invite",0,"created",[],["email"=>$email]);
		$this->jsonResponse(["success"=>true,"email"=>$email]);
	}

	public function accept(): void {
		// Public — referral candidate registers via invite
		$token = $this->getInput("token");
		$db = Database::getInstance();
		$inv = $db->prepare("SELECT * FROM referral_invites WHERE token=:t AND status='pending' AND expires_at>NOW()");
		$inv->execute(["t"=>$token]);
		$inv=$inv->fetch();
		if (!$inv) {
			$this->setFlash("error","Referral link invalid or expired");
			$this->redirect("index.php?page=auth&action=login");
			return;
		}
		// Store in session and redirect to register
		$_SESSION["referral_token"] = $token;
		$_SESSION["referral_email"] = $inv["candidate_email"];
		$_SESSION["referral_name"]  = $inv["candidate_name"];
		$this->redirect("index.php?page=auth&action=register");
	}
}