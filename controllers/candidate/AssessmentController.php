<?php 
declare(strict_types=1);

class AssessmentController extends BaseController {
	public function index(): void {
		$this->requireRole("candidate");
		$db=Database::getInstance();
		$stmt=$db->prepare("SELECT a.*, j.title as job_title FROM assessments a JOIN job_requisitions j ON a.job_id=j.id JOIN applications app ON app.job_id=a.job_id WHERE app.candidate_id=:cid AND app.stage IN ('technical_test','screening')");
		$stmt->execute(["cid"=>$this->currentUser["id"]]);
		$assessments=$stmt->fetchAll();
		$pageTitle="My Assessments";
		ob_start();
		?>
		<h1 class="text-2xl font-bold mb-6">Assessments</h1>
			<div class="grid grid-cols-1 md:grid-cols-2 gap-4"><?php foreach($assessments as $a):?>
					<div class="bg-white rounded-xl border p-6 card-hover">
						<h3 class="font-semibold"><?=htmlspecialchars($a["title"])?></h3>
							<p class="text-sm text-slate-500 mb-3"><?=htmlspecialchars($a["job_title"])?> - <?=$a["total_time_minutes"]?> minutes</p>
								<a href="index.php?page=candidate/assessments&action=start&id=<?=$a["id"]?>" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg inline-block">Start Assessment</a>
									</div>
									<?php endforeach;
		if(empty($assessments)):?><div class="text-center py-12 bg-white rounded-xl border col-span-2"><p class="text-slate-500">No assessments available</p></div><?php endif;
		?></div>
		<?php $content=ob_get_clean();
		$this->renderLayout($content,compact("pageTitle"));
	}

	public function start(): void {
		$this->requireRole("candidate");
		$assessmentId=$this->getIntInput("id");
		try {
			(new CooldownService())->enforce($assessmentId,(int)$this->currentUser["id"]);
		}
		catch(\Exception $e) {
			$this->setFlash("error",$e->getMessage());
			$this->redirect("index.php?page=candidate/assessments");
			return;
		}
		$qbs=new QuestionBankService();
		$questions=$qbs->generateTest($assessmentId,(int)$this->currentUser["id"]);
		$db=Database::getInstance();
		$stmt=$db->prepare("INSERT INTO candidate_sessions (candidate_id,assessment_id,questions_json,status) VALUES(:cid,:aid,:qj,'active')");
		$qIds=array_column($questions,"id");
		$stmt->execute(["cid"=>$this->currentUser["id"],"aid"=>$assessmentId,"qj"=>json_encode($qIds)]);
		$sessionId=(int)$db->lastInsertId();
		$this->redirect("index.php?page=candidate/assessments&action=take&session_id=$sessionId");
	}

	public function take(): void {
		$this->requireRole("candidate");
		$sessionId=$this->getIntInput("session_id");
		$db=Database::getInstance();
		$stmt=$db->prepare("SELECT cs.*, a.total_time_minutes, a.title FROM candidate_sessions cs JOIN assessments a ON cs.assessment_id=a.id WHERE cs.id=:sid AND cs.candidate_id=:cid");
		$stmt->execute(["sid"=>$sessionId,"cid"=>$this->currentUser["id"]]);
		$session=$stmt->fetch();
		if(!$session||$session["status"]!=="active") {
			$this->setFlash("error","Session expired or not found");
			$this->redirect("index.php?page=candidate/assessments");
			return;
		}

		if($_SERVER["REQUEST_METHOD"]==="POST"&&$this->validateCsrf()) {
			$qIds=json_decode($session["questions_json"],true)?:[];
			foreach($qIds as $qId) {
				$answer=$_POST["answer_$qId"]??"";
				$db->prepare("INSERT INTO candidate_answers (session_id,question_id,answer_text) VALUES(:sid,:qid,:ans) ON DUPLICATE KEY UPDATE answer_text=:ans2")->execute(["sid"=>$sessionId,"qid"=>$qId,"ans"=>$answer,"ans2"=>$answer]);
			}
			$db->prepare("UPDATE candidate_sessions SET status='submitted', submitted_at=NOW() WHERE id=:id")->execute(["id"=>$sessionId]);
			AuditLogger::getInstance()->log((int)$this->currentUser["id"],"candidate_session",$sessionId,"submitted",[],[]);
			$this->setFlash("success","Assessment submitted!");
			$this->redirect("index.php?page=candidate/assessments");
			return;
		}

		$qIds=json_decode($session["questions_json"],true)?:[];
		$questions=[];
		foreach($qIds as $qId) {
			$q=(new QuestionModel())->findById((int)$qId);
			if($q)$questions[]=$q;
		}
		$heartbeat=(new SessionHeartbeatService())->check($sessionId);
		$maxStrikes = defined("PROCTORING_MAX_STRIKES") ? PROCTORING_MAX_STRIKES : 3;
		$pageTitle=$session["title"];
		ob_start();
		?>
		<div class="max-w-3xl mx-auto">
			           <div id="tabWarningModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden">
				                   <div class="bg-white rounded-xl p-8 max-w-md mx-4 shadow-2xl text-center">
					                              <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-100 flex items-center justify-center"><svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg></div>
							                                      <h3 class="text-xl font-bold text-slate-800 mb-2">Tab Switch Detected</h3>
								                                              <p class="text-slate-600 mb-2">Leaving this page during an assessment is a violation.</p>
									                                                      <p class="text-red-600 font-semibold mb-4">Strike <span id="strikeCount">0</span> of <?=$maxStrikes?></p>
										                                                              <div class="bg-red-50 rounded-lg p-3 mb-4 text-sm text-red-700">After <?=$maxStrikes?> strikes, your assessment will be auto-flagged and your integrity score will be significantly reduced.</div>
											                                                                      <button onclick="dismissWarning()" class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700">I Understand, Continue</button>
												                                                                              </div>
												                                                                              </div>

												                                                                              <div class="bg-white rounded-xl border p-4 mb-4 flex justify-between items-center sticky top-0 z-10 shadow">
													                                                                                      <h2 class="font-bold"><?=htmlspecialchars($session["title"])?></h2>
														                                                                                              <div class="flex items-center gap-4">
															                                                                                                      <span class="text-sm text-slate-500">Integrity: <strong class="<?=$session["integrity_score"]<70?"text-red-600":"text-emerald-600"?>" id="integrityScore"><?=$session["integrity_score"]?>%</strong></span>
																	                                                                                                              <span class="text-xs px-2 py-1 rounded-lg bg-slate-100" id="strikesBadge">Strikes: 0/<?=$maxStrikes?></span>
																		                                                                                                                      <div class="bg-red-100 text-red-700 px-4 py-2 rounded-lg font-mono font-bold" id="timer">--:--</div>
																			                                                                                                                              </div>
																			                                                                                                                              </div>
																			                                                                                                                              <form method="POST" id="assessmentForm"><?=$this->csrfField()?>
																			                                                                                                                                      <?php foreach($questions as $i=>$q):?>
																			                                                                                                                                      <div class="bg-white rounded-xl border p-6 mb-4" id="q-<?=$i?>">
																				                                                                                                                                              <div class="flex justify-between mb-2"><span class="text-sm font-semibold text-indigo-600">Q<?=$i+1?></span><span class="px-2 py-0.5 rounded text-xs <?=$q["difficulty"]==="easy"?"bg-emerald-100 text-emerald-700":($q["difficulty"]==="hard"?"bg-red-100 text-red-700":"bg-amber-100 text-amber-700")?>"><?=ucfirst($q["difficulty"])?></span></div>
																							                                                                                                                                                      <p class="text-slate-800 mb-3"><?=htmlspecialchars($q["content"])?></p>
																								                                                                                                                                                              <?php if($q["type"]==="mcq"&&$q["options_json"]):$opts=json_decode($q["options_json"],true)?:[];
		foreach($opts as $oi=>$opt):?>
			<label class="flex items-center gap-2 p-2 rounded hover:bg-slate-50 cursor-pointer"><input type="radio" name="answer_<?=$q["id"]?>" value="<?=htmlspecialchars($opt)?>"><span class="text-sm"><?=htmlspecialchars($opt)?></span></label>
					             <?php endforeach;
		elseif($q["type"]==="coding"):?>
			<textarea name="answer_<?=$q["id"]?>" rows="8" class="w-full p-3 font-mono text-sm bg-slate-900 text-green-400 rounded-lg" placeholder="// Write your code here..."></textarea>
				               <?php else:?><textarea name="answer_<?=$q["id"]?>" rows="4" class="w-full p-3 border rounded-lg text-sm" placeholder="Your answer..."></textarea><?php endif;
		?>
		</div>
		<?php endforeach;
		?>
		<button type="submit" class="w-full py-3 bg-indigo-600 text-white rounded-lg font-semibold text-lg hover:bg-indigo-700">Submit Assessment</button>
			                            </form>
			                            </div>
			                            <script>
		// Timer
			                            let remaining=<?=$heartbeat["remaining"]??0?>;
		const timerEl=document.getElementById("timer");
		setInterval(()=>{if(remaining<=0) {
				document.getElementById("assessmentForm").submit();
				return;
			}
			remaining--;
			let m=Math.floor(remaining/60),s=remaining%60; timerEl.textContent=String(m).padStart(2,"0")+":"+String(s).padStart(2,"0");
			if(remaining<60)timerEl.classList.add("animate-pulse");},1000);

		// Tab-switch detection with warning popup
		let strikes=0;
		const maxStrikes=<?=$maxStrikes?>;
		const modal=document.getElementById("tabWarningModal");
		const strikeCountEl=document.getElementById("strikeCount");
		const strikesBadge=document.getElementById("strikesBadge");
		const integrityEl=document.getElementById("integrityScore");

		function showWarning() {
			strikes++;
			strikeCountEl.textContent=strikes;
			strikesBadge.textContent="Strikes: "+strikes+"/"+maxStrikes;
			if(strikes>=maxStrikes) {
				strikesBadge.classList.add("bg-red-100","text-red-700");
				strikesBadge.classList.remove("bg-slate-100");
			}
			modal.classList.remove("hidden");
			// Log to server
			fetch("index.php?page=candidate/assessments&action=proctor_event&session_id=<?=$sessionId?>&type=tab_switch&strikes="+strikes)
			.then(r=>r.json()).then(d=> {
				if(d.integrity!==undefined) {
					integrityEl.textContent=d.integrity+"%";
					if(d.integrity<70)integrityEl.className="text-red-600 font-bold";
				}
			});
		}
		function dismissWarning() {
			modal.classList.add("hidden");
		}

		document.addEventListener("visibilitychange",()=>{if(document.hidden)showWarning();});
		window.addEventListener("blur",()=>{
			fetch("index.php?page=candidate/assessments&action=proctor_event&session_id=<?=$sessionId?>&type=window_blur");
		});
		// Heartbeat
		setInterval(()=>fetch("index.php?page=candidate/assessments&action=heartbeat&session_id=<?=$sessionId?>"),30000);
		</script>
		<?php $content=ob_get_clean();
		$this->renderLayout($content,compact("pageTitle"));
	}

	public function proctor_event(): void {
		$sessionId=$this->getIntInput("session_id");
		$type=$this->getInput("type","tab_switch");
		$strikes=$this->getIntInput("strikes",0);
		(new ProctoringService())->logEvent($sessionId,$type);
		// Update integrity score
		$db=Database::getInstance();
		$stmt=$db->prepare("SELECT integrity_score FROM candidate_sessions WHERE id=:id");
		$stmt->execute(["id"=>$sessionId]);
		$current=$stmt->fetch();
		$newScore=max(0,(int)($current["integrity_score"]??100) - PROCTORING_PENALTY);
		$db->prepare("UPDATE candidate_sessions SET integrity_score=:s WHERE id=:id")->execute(["s"=>$newScore,"id"=>$sessionId]);
		if($strikes >= PROCTORING_MAX_STRIKES) {
			$db->prepare("UPDATE candidate_sessions SET is_flagged=1 WHERE id=:id")->execute(["id"=>$sessionId]);
			AuditLogger::getInstance()->log((int)$this->currentUser["id"],"candidate_session",$sessionId,"auto_flagged_proctoring",[],["strikes"=>$strikes]);
		}
		$this->jsonResponse(["ok"=>true,"integrity"=>$newScore,"flagged"=>$strikes>=PROCTORING_MAX_STRIKES]);
	}

	public function heartbeat(): void {
		$sessionId=$this->getIntInput("session_id");
		$result=(new SessionHeartbeatService())->check($sessionId);
		$this->jsonResponse($result);
	}
}