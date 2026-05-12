<?php 
declare(strict_types=1);
class OfferController extends BaseController {
    
	public function index(): void {
		$this->requireRole("hr_admin");
		$om=new OfferModel();
		$offers=$om->findAll();
		$pageTitle="Offers";
		ob_start();
		?>
		<div class="flex justify-between items-center mb-6"><h1 class="text-2xl font-bold">Offer Management</h1></div>
				<div class="bg-white rounded-xl border"><table class="w-full text-sm"><thead><tr class="border-b text-left text-slate-500"><th class="p-4">ID</th><th class="p-4">Application</th><th class="p-4">Salary</th><th class="p-4">Status</th><th class="p-4">Email</th><th class="p-4">Actions</th></tr></thead><tbody>
													<?php foreach($offers as $o): $sc=["pending"=>"bg-slate-100 text-slate-700","sent"=>"bg-blue-100 text-blue-700","accepted"=>"bg-emerald-100 text-emerald-700","declined"=>"bg-red-100 text-red-700","expired"=>"bg-amber-100 text-amber-700"];
		?>
		<tr class="border-b hover:bg-slate-50"><td class="p-4">#<?=$o["id"]?></td><td class="p-4">App #<?=$o["application_id"]?></td><td class="p-4 font-semibold">$<?=number_format((float)$o["salary"],0)?></td><td class="p-4"><span class="px-2 py-0.5 rounded-full text-xs font-semibold <?=$sc[$o["status"]]??""?>"><?=ucfirst($o["status"])?></span></td>
								<td class="p-4"><?=$o["email_sent"]?"<span class=\"text-emerald-600 text-xs\">Sent</span>":"<span class=\"text-slate-400 text-xs\">Not sent</span>"?></td>
									<td class="p-4 flex gap-2">
										<a href="index.php?page=hr/offers&action=view&id=<?=$o["id"]?>" class="text-indigo-600 hover:underline text-xs">View</a>
											<?php if($o["status"]==="pending"):?><a href="index.php?page=hr/offers&action=send&id=<?=$o["id"]?>" class="text-blue-600 hover:underline text-xs">Send</a><?php endif;
		?>
		<a href="index.php?page=hr/offers&action=download&id=<?=$o["id"]?>" class="text-emerald-600 hover:underline text-xs">PDF</a>
			<?php if($o["status"]==="sent"&&!$o["email_sent"]):?><a href="index.php?page=hr/offers&action=send_email&id=<?=$o["id"]?>" class="text-purple-600 hover:underline text-xs">Email</a><?php endif;
		?>
		</td></tr>

		<?php endforeach;
		?></tbody></table></div>
		<?php $content=ob_get_clean();
		$this->renderLayout($content,compact("pageTitle"));
	}

	public function create(): void {
		$this->requireRole("hr_admin");
		$appId=$this->getIntInput("app_id");
		$app=(new ApplicationModel())->getWithDetails($appId);
		if(!$app) {
			$this->setFlash("error","Application not found");
			$this->redirect("index.php?page=hr/offers");
			return;
		}
		if($_SERVER["REQUEST_METHOD"]==="POST"&&$this->validateCsrf()) {
			$calc=new OfferCalculatorService();
			$pkg=$calc->calculate($app["level"]??"",$app["location_tier"]??"tier1");
			$om=new OfferModel();
			$id=$om->create(["application_id"=>$appId,"salary"=>$pkg["base_salary"],"signing_bonus"=>$pkg["signing_bonus"],"equity"=>$pkg["equity_units"],"created_by"=>$this->currentUser["id"]]);
			AuditLogger::getInstance()->log((int)$this->currentUser["id"],"offer",$id,"created",[],["salary"=>$pkg["base_salary"]]);
			// Email notification to HR admins
			foreach((new UserModel())->findByRole("hr_admin") as $admin) {
				EmailService::getInstance()->sendTemplate($admin["email"],"New Offer Created","job_created",["title"=>"Offer #$id for ".$app["candidate_name"],"department"=>$app["department"]??"","level"=>$app["level"]??""]);
			}
			$this->setFlash("success","Offer created");
			$this->redirect("index.php?page=hr/offers&action=view&id=$id");
			return;
		}
		$calc=new OfferCalculatorService();
		$pkg=$calc->calculate($app["level"]??"",$app["location_tier"]??"tier1");
		$pageTitle="Create Offer";
		ob_start();
		?>
		<div class="max-w-2xl mx-auto bg-white rounded-xl border p-8">
			           <h2 class="text-xl font-bold mb-4">Create Offer for <?=htmlspecialchars($app["candidate_name"])?></h2>
					                     <p class="text-slate-500 mb-6"><?=htmlspecialchars($app["job_title"])?> - <?=$app["level"]?></p>
						                              <div class="bg-indigo-50 rounded-lg p-4 mb-6"><h3 class="font-semibold text-indigo-700 mb-2">Calculated Package</h3>
								                                      <div class="grid grid-cols-3 gap-4 text-sm"><div><p class="text-slate-600">Base Salary</p><p class="text-xl font-bold text-indigo-700">$<?=number_format($pkg["base_salary"],0)?></p></div><div><p class="text-slate-600">Signing Bonus</p><p class="text-xl font-bold text-purple-700">$<?=number_format($pkg["signing_bonus"],0)?></p></div><div><p class="text-slate-600">Equity Units</p><p class="text-xl font-bold text-emerald-700"><?=$pkg["equity_units"]?></p></div></div></div>
															                                              <form method="POST"><?=$this->csrfField()?><button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700">Create Offer</button></form>
																                                                      </div>
																                                                      <?php $content=ob_get_clean();
		$this->renderLayout($content,compact("pageTitle"));
	}

	public function view(): void {
		$this->requireRole("hr_admin");
		$id=$this->getIntInput("id");
		$om=new OfferModel();
		$offer=$om->findById($id);
		if(!$offer) {
			$this->setFlash("error","Offer not found");
			$this->redirect("index.php?page=hr/offers");
			return;
		}
		$negotiations=$om->getNegotiations($id);
		$letterSvc=new OfferLetterService();
		$letterHtml="";
		try {
			$letterHtml=$letterSvc->generate($id);
		}
		catch(\Exception $e) {
			$letterHtml="<p class=\"text-red-500\">Error generating letter</p>";
		}
		$pageTitle="Offer #$id";
		ob_start();
		?>
		<div class="max-w-4xl mx-auto">
			<div class="bg-white rounded-xl border p-6 mb-6">
				<div class="flex justify-between items-center mb-4"><h2 class="text-xl font-bold">Offer #<?=$id?></h2>
						<div class="flex gap-2">
							<?php if($offer["status"]==="pending"):?><a href="index.php?page=hr/offers&action=send&id=<?=$id?>" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition">Send Offer</a><?php endif;
		?>
		<?php if($offer["status"]==="sent"&&!$offer["email_sent"]):?><a href="index.php?page=hr/offers&action=send_email&id=<?=$id?>" class="px-4 py-2 bg-purple-600 text-white text-sm rounded-lg hover:bg-purple-700 transition">Email + PDF</a><?php endif;
		?>
		<?php if($offer["email_sent"]):?><a href="index.php?page=hr/offers&action=send_email&id=<?=$id?>" class="px-4 py-2 bg-purple-500 text-white text-sm rounded-lg hover:bg-purple-600 transition">Resend Email + PDF</a><?php endif;
		?>
		<a href="index.php?page=hr/offers&action=download&id=<?=$id?>" class="px-4 py-2 bg-emerald-600 text-white text-sm rounded-lg hover:bg-emerald-700 transition flex items-center gap-2">
			<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
				Download PDF
				</a>
				</div></div>

				<div class="grid grid-cols-4 gap-4"><div><p class="text-sm text-slate-500">Salary</p><p class="font-bold text-lg">$<?=number_format((float)$offer["salary"],0)?></p></div><div><p class="text-sm text-slate-500">Bonus</p><p class="font-bold">$<?=number_format((float)$offer["signing_bonus"],0)?></p></div><div><p class="text-sm text-slate-500">Equity</p><p class="font-bold"><?=$offer["equity"]?> units</p></div><div><p class="text-sm text-slate-500">Status</p><p class="font-bold text-indigo-600"><?=ucfirst($offer["status"])?></p></div></div>
													</div>
													<div class="bg-white rounded-xl border p-6 mb-6"><h3 class="font-semibold mb-4">Negotiation History</h3>
															<?php if(empty($negotiations)):?><p class="text-slate-500 text-sm">No negotiations yet</p>
																	<?php else: foreach($negotiations as $n):?><div class="flex items-center gap-4 p-3 bg-slate-50 rounded-lg mb-2"><span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded">Rev <?=$n["revision_number"]?></span><span class="font-semibold">$<?=number_format((float)$n["proposed_salary"],0)?></span><span class="text-sm text-slate-500">by <?=ucfirst($n["proposed_by"])?></span></div><?php endforeach;
		endif;
		?>
		<form method="POST" action="index.php?page=hr/offers&action=negotiate&id=<?=$id?>" class="mt-4 flex gap-2"><?=$this->csrfField()?><input name="salary" type="number" step="1000" class="px-3 py-2 border rounded-lg text-sm w-40" placeholder="New salary"><input name="notes" class="flex-1 px-3 py-2 border rounded-lg text-sm" placeholder="Notes"><button class="px-4 py-2 bg-purple-600 text-white text-sm rounded-lg">Add Round</button></form>
						</div>
						<div class="bg-white rounded-xl border p-6"><h3 class="font-semibold mb-4">Offer Letter Preview</h3><?=$letterHtml?></div>
								</div>
								<?php $content=ob_get_clean();
		$this->renderLayout($content,compact("pageTitle"));
	}

	public function send(): void {
		$this->requireRole("hr_admin");
		$id=$this->getIntInput("id");
		(new OfferValidityService())->send($id);
		$this->setFlash("success","Offer sent");
		$this->redirect("index.php?page=hr/offers&action=view&id=$id");
	}

	public function send_email(): void {
		$this->requireRole("hr_admin");
		$id = $this->getIntInput("id");
		$om = new OfferModel();
		$offer = $om->findById($id);
		if (!$offer) {
			$this->setFlash("error","Offer not found");
			$this->redirect("index.php?page=hr/offers");
			return;
		}
		$app = (new ApplicationModel())->getWithDetails((int)$offer["application_id"]);

		// Get candidate email
		$candidateEmail = $app["email"] ?? "";
		if (empty($candidateEmail)) {
			$candidate = (new UserModel())->findById((int)$app["candidate_id"]);
			$candidateEmail = $candidate["email"] ?? "";
		}
		if (empty($candidateEmail)) {
			$this->setFlash("error","Candidate email not found");
			$this->redirect("index.php?page=hr/offers&action=view&id=$id");
			return;
		}

		// Generate PDF
		$pdfPath = null;
		try {
			require_once __DIR__ . "/../../services/offer/PdfGeneratorService.php";
			$pdfPath = (new PdfGeneratorService())->generateOfferLetter($id);
		} catch (\Throwable $e) {
			error_log("PDF generation failed: " . $e->getMessage());
		}

		$link = BASE_URL . "/index.php?page=candidate/applications&action=offer&id=" . $offer["application_id"];
		$vars = ["name"=>$app["candidate_name"]??"","job_title"=>$app["job_title"]??"","salary"=>number_format((float)$offer["salary"],0),"bonus"=>number_format((float)$offer["signing_bonus"],0),"link"=>$link];
		$bodyHtml = ""; // rendered inline
		$es = EmailService::getInstance();

		// Send to candidate (with PDF if available)
		if ($pdfPath && file_exists($pdfPath)) {
			$bodyHtml = $es->renderPublicTemplate("offer_created", $vars);
			$es->sendWithAttachment($candidateEmail, "Your Offer Letter from NextHire", $bodyHtml, $pdfPath, "offer_letter.pdf");
			// Copy to HR Admin too
			$es->sendWithAttachment($this->currentUser["email"], "Offer Letter Sent — " . ($app["candidate_name"]??""), $bodyHtml, $pdfPath, "offer_letter.pdf");
		} else {
			$es->sendTemplate($candidateEmail, "Your Offer from NextHire", "offer_created", $vars);
			$es->sendTemplate($this->currentUser["email"], "Offer Letter Sent", "offer_created", $vars);
		}

		$om->update($id, ["email_sent"=>1]);
		AuditLogger::getInstance()->log((int)$this->currentUser["id"],"offer",$id,"email_sent",[],["to"=>$candidateEmail]);
		$this->setFlash("success","Offer letter emailed to candidate" . ($pdfPath ? " with PDF attachment" : ""));
		$this->redirect("index.php?page=hr/offers&action=view&id=$id");
	}

	public function download(): void {
		$this->requireRole("hr_admin");
		$id = $this->getIntInput("id");
		$om = new OfferModel();
		$offer = $om->findById($id);
		if (!$offer) {
			$this->setFlash("error","Offer not found");
			$this->redirect("index.php?page=hr/offers");
			return;
		}

		// Generate if not exists
		$pdfPath = null;
		if (!empty($offer["pdf_path"])) {
			$pdfPath = __DIR__ . "/../../" . $offer["pdf_path"];
		}
		if (!$pdfPath || !file_exists($pdfPath)) {
			try {
				require_once __DIR__ . "/../../services/offer/PdfGeneratorService.php";
				$pdfPath = (new PdfGeneratorService())->generateOfferLetter($id);
			} catch (\Throwable $e) {
				$this->setFlash("error","Could not generate PDF: " . $e->getMessage());
				$this->redirect("index.php?page=hr/offers&action=view&id=$id");
				return;
			}
		}
		header("Content-Type: application/pdf");
		header("Content-Disposition: attachment; filename=\"offer_letter_$id.pdf\"");
		header("Content-Length: " . filesize($pdfPath));
		readfile($pdfPath);
		exit;
	}



	public function check_expiry(): void {
		$this->requireRole("hr_admin");
		$id=$this->getIntInput("id");
		(new OfferValidityService())->checkExpiry($id);
		$this->setFlash("success","Expiry checked");
		$this->redirect("index.php?page=hr/offers&action=view&id=$id");
	}

	public function negotiate(): void {
		$this->requireRole("hr_admin");
		$id=$this->getIntInput("id");
		if($this->validateCsrf()) {
			$salary=(float)($_POST["salary"]??0);
			$notes=$_POST["notes"]??"";
			(new NegotiationTrackerService())->addRound($id,"company",$salary,$notes);
			$this->setFlash("success","Negotiation round added");
		}
		$this->redirect("index.php?page=hr/offers&action=view&id=$id");
	}
}