<?php 
declare(strict_types=1);

class DashboardController extends BaseController {
    
	public function index(): void {
		$this->requireAuth();
		match($this->currentUser["role"]) {
			"hr_admin"     => $this->hrDashboard(),
			"dept_manager" => $this->redirect("index.php?page=dept_manager"),
			"interviewer"  => $this->interviewerDashboard(),
			"shadow"       => $this->shadowDashboard(),
			"candidate"    => $this->candidateDashboard(),
			default        => $this->candidateDashboard(),
		};
	}

	private function hrDashboard(): void {
		$db=Database::getInstance();
		$totalJobs=(int)$db->query("SELECT COUNT(*) FROM job_requisitions WHERE status='live'")->fetchColumn();
		$totalApps=(int)$db->query("SELECT COUNT(*) FROM applications")->fetchColumn();
		$pendingFeedback=(int)$db->query("SELECT COUNT(DISTINCT ip.id) FROM interview_panels ip LEFT JOIN feedback_submissions fs ON fs.panel_id=ip.id WHERE ip.status='completed' AND fs.id IS NULL")->fetchColumn();
		$pendingOffers=(int)$db->query("SELECT COUNT(*) FROM offers WHERE status IN ('pending','sent')")->fetchColumn();
		$recentApps=$db->query("SELECT a.*, u.name as candidate_name, j.title as job_title FROM applications a JOIN users u ON a.candidate_id=u.id JOIN job_requisitions j ON a.job_id=j.id ORDER BY a.applied_at DESC LIMIT 5")->fetchAll();
		$notifs=(new NotificationModel())->getUnread((int)$this->currentUser["id"]);
		$pageTitle="HR Dashboard";
		ob_start();
		?>
		<h1 class="text-2xl font-bold mb-6">HR Admin Dashboard</h1>
			<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
				<div class="bg-white rounded-xl border p-5 card-hover"><p class="text-sm text-slate-500">Active Jobs</p><p class="text-3xl font-bold text-indigo-600"><?=$totalJobs?></p></div>
							<div class="bg-white rounded-xl border p-5 card-hover"><p class="text-sm text-slate-500">Applications</p><p class="text-3xl font-bold text-purple-600"><?=$totalApps?></p></div>
										<div class="bg-white rounded-xl border p-5 card-hover"><p class="text-sm text-slate-500">Pending Feedback</p><p class="text-3xl font-bold text-amber-600"><?=$pendingFeedback?></p></div>
													<div class="bg-white rounded-xl border p-5 card-hover"><p class="text-sm text-slate-500">Open Offers</p><p class="text-3xl font-bold text-emerald-600"><?=$pendingOffers?></p></div>
																</div>
																<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
																	<div class="bg-white rounded-xl border p-6"><h3 class="font-semibold mb-4">Recent Applications</h3>
																			<?php foreach($recentApps as $a):?><div class="flex justify-between items-center p-3 bg-slate-50 rounded-lg mb-2"><div><p class="font-medium text-sm"><?=htmlspecialchars($a["candidate_name"])?></p><p class="text-xs text-slate-500"><?=htmlspecialchars($a["job_title"])?></p></div><span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700"><?=ucfirst(str_replace("_"," ",$a["stage"]))?></span></div><?php endforeach;
		?></div>
		<div class="bg-white rounded-xl border p-6"><h3 class="font-semibold mb-4">Quick Actions</h3><div class="space-y-2">
					<a href="index.php?page=hr/jobs&action=create" class="block p-3 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition text-sm font-medium text-indigo-700">Create New Job Requisition</a>
						<a href="index.php?page=hr/pipeline" class="block p-3 bg-purple-50 rounded-lg hover:bg-purple-100 transition text-sm font-medium text-purple-700">View Pipeline Kanban</a>
							<a href="index.php?page=hr/analytics" class="block p-3 bg-amber-50 rounded-lg hover:bg-amber-100 transition text-sm font-medium text-amber-700">Pipeline Analytics</a>
								<a href="index.php?page=hr/interviews" class="block p-3 bg-cyan-50 rounded-lg hover:bg-cyan-100 transition text-sm font-medium text-cyan-700">Interview Management</a>
									<a href="index.php?page=interviewer/schedule" class="block p-3 bg-teal-50 rounded-lg hover:bg-teal-100 transition text-sm font-medium text-teal-700">Interview Schedule</a>
										<a href="index.php?page=hr/compliance" class="block p-3 bg-emerald-50 rounded-lg hover:bg-emerald-100 transition text-sm font-medium text-emerald-700">Compliance & Audit</a>
											</div></div>
											</div>
											<?php if(!empty($notifs)):?><div class="mt-6 bg-white rounded-xl border p-6"><h3 class="font-semibold mb-4">Notifications</h3>
														<?php foreach(array_slice($notifs,0,5) as $n):?><div class="p-3 bg-slate-50 rounded-lg mb-2 flex justify-between"><span class="text-sm"><?=htmlspecialchars($n["message"])?></span><span class="text-xs text-slate-400"><?=$n["created_at"]?></span></div><?php endforeach;
		?></div><?php endif;
		?>
		<?php $content=ob_get_clean();
		$this->renderLayout($content,compact("pageTitle"));
	}

	private function interviewerDashboard(): void {
		$db=Database::getInstance();
		$stmt=$db->prepare("SELECT ip.*,j.title as job_title FROM interview_panels ip JOIN job_requisitions j ON ip.job_id=j.id JOIN panel_members pm ON pm.panel_id=ip.id WHERE pm.user_id=:uid AND ip.status='scheduled' ORDER BY ip.scheduled_at");
		$stmt->execute(["uid"=>$this->currentUser["id"]]);
		$upcoming=$stmt->fetchAll();
		$fm=new FeedbackModel();
		$pending=$fm->getPendingByInterviewer((int)$this->currentUser["id"]);
		$pageTitle="Interviewer Dashboard";
		ob_start();
		?>
		<h1 class="text-2xl font-bold mb-6">Interviewer Dashboard</h1>
			<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
				<div class="bg-white rounded-xl border p-5"><p class="text-sm text-slate-500">Upcoming Interviews</p><p class="text-3xl font-bold text-indigo-600"><?=count($upcoming)?></p></div>
							<div class="bg-white rounded-xl border p-5"><p class="text-sm text-slate-500">Pending Feedback</p><p class="text-3xl font-bold text-amber-600"><?=count($pending)?></p></div>
										</div>
										<div class="bg-white rounded-xl border p-6 mb-6"><h3 class="font-semibold mb-4">Upcoming Interviews</h3>
												<?php foreach($upcoming as $u):?><div class="p-4 bg-slate-50 rounded-lg mb-3 flex justify-between items-center"><div><p class="font-medium"><?=htmlspecialchars($u["job_title"])?></p><p class="text-sm text-slate-500"><?=$u["scheduled_at"]?> - <?=$u["duration_minutes"]?>min</p></div>
															<a href="index.php?page=interviewer/live&action=join&id=<?=$u["id"]?>" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg">Join Session</a></div><?php endforeach;
		if(empty($upcoming)):?><p class="text-slate-500 text-center py-4">No upcoming interviews</p><?php endif;
		?></div>
		<?php if(!empty($pending)):?><div class="bg-white rounded-xl border p-6"><h3 class="font-semibold mb-4">Feedback Required</h3>
					<?php foreach($pending as $p):?><div class="p-3 bg-amber-50 rounded-lg mb-2 flex justify-between items-center"><span class="text-sm"><?=htmlspecialchars($p["job_title"]??"")?> - Panel #<?=$p["panel_id"]?></span>
							<a href="index.php?page=interviewer/feedback&action=submit&id=<?=$p["panel_id"]?>" class="px-3 py-1 bg-indigo-600 text-white text-xs rounded-lg">Submit</a></div><?php endforeach;
		?></div><?php endif;
		?>
		<?php $content=ob_get_clean();
		$this->renderLayout($content,compact("pageTitle"));
	}

	private function shadowDashboard(): void {
		$db=Database::getInstance();
		$stmt=$db->prepare("SELECT ip.*,j.title as job_title FROM interview_panels ip JOIN job_requisitions j ON ip.job_id=j.id JOIN panel_members pm ON pm.panel_id=ip.id WHERE pm.user_id=:uid AND pm.role='shadow' ORDER BY ip.scheduled_at DESC");
		$stmt->execute(["uid"=>$this->currentUser["id"]]);
		$panels=$stmt->fetchAll();
		$pageTitle="Shadow Observer Dashboard";
		ob_start();
		?>
		<h1 class="text-2xl font-bold mb-6">Shadow Observer Dashboard</h1>
			<div class="bg-white rounded-xl border p-6"><h3 class="font-semibold mb-4">Your Observation Sessions</h3>
					<p class="text-sm text-slate-500 mb-4">As a shadow observer, you can view live sessions in read-only mode.</p>
						<?php foreach($panels as $p):?><div class="p-4 bg-slate-50 rounded-lg mb-3 flex justify-between items-center"><div><p class="font-medium"><?=htmlspecialchars($p["job_title"])?></p><p class="text-sm text-slate-500"><?=$p["scheduled_at"]?> - <?=$p["status"]?></p></div>
									<?php if($p["status"]==="scheduled"):?><a href="index.php?page=interviewer/live&action=join&id=<?=$p["id"]?>" class="px-4 py-2 bg-slate-600 text-white text-sm rounded-lg">Observe</a><?php endif;
		?></div><?php endforeach;
		if(empty($panels)):?><p class="text-slate-500 text-center py-4">No observation sessions assigned</p><?php endif;
		?></div>
		<?php $content=ob_get_clean();
		$this->renderLayout($content,compact("pageTitle"));
	}

	private function candidateDashboard(): void {
		$appModel=new ApplicationModel();
		$apps=$appModel->findByCandidate((int)$this->currentUser["id"]);
		$db=Database::getInstance();
		$stmt=$db->prepare("SELECT ip.*,j.title as job_title FROM interview_panels ip JOIN applications a ON ip.application_id=a.id JOIN job_requisitions j ON ip.job_id=j.id WHERE a.candidate_id=:cid AND ip.status='scheduled'");
		$stmt->execute(["cid"=>$this->currentUser["id"]]);
		$interviews=$stmt->fetchAll();
		$pageTitle="Candidate Dashboard";
		ob_start();
		?>
		<h1 class="text-2xl font-bold mb-6">Welcome, <?=htmlspecialchars($this->currentUser["name"])?></h1>
			<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
				<div class="bg-white rounded-xl border p-5"><p class="text-sm text-slate-500">My Applications</p><p class="text-3xl font-bold text-indigo-600"><?=count($apps)?></p></div>
							<div class="bg-white rounded-xl border p-5"><p class="text-sm text-slate-500">Upcoming Interviews</p><p class="text-3xl font-bold text-purple-600"><?=count($interviews)?></p></div>
										<div class="bg-white rounded-xl border p-5"><p class="text-sm text-slate-500">Pending Offers</p><p class="text-3xl font-bold text-emerald-600"><?=count(array_filter($apps,fn($a)=>$a["stage"]==="offer"))?></p></div>
													</div>
													<?php if(!empty($interviews)):?><div class="bg-white rounded-xl border p-6 mb-6"><h3 class="font-semibold mb-4">Upcoming Interviews</h3>
																<?php foreach($interviews as $iv):?><div class="p-4 bg-indigo-50 rounded-lg mb-3 flex justify-between items-center"><div><p class="font-medium"><?=htmlspecialchars($iv["job_title"])?></p><p class="text-sm text-slate-500"><?=$iv["scheduled_at"]?> - <?=$iv["duration_minutes"]?>min</p></div>
																			<a href="index.php?page=candidate/interview&action=join&id=<?=$iv["id"]?>" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg">Join Interview</a></div><?php endforeach;
		?></div><?php endif;
		?>
		<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
			<div class="bg-white rounded-xl border p-6"><h3 class="font-semibold mb-4">Quick Actions</h3><div class="space-y-2">
						<a href="index.php?page=candidate/applications&action=browse" class="block p-3 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition text-sm font-medium text-indigo-700">Browse Available Jobs</a>
							<a href="index.php?page=candidate/applications" class="block p-3 bg-purple-50 rounded-lg hover:bg-purple-100 transition text-sm font-medium text-purple-700">My Applications</a>
								<a href="index.php?page=candidate/assessments" class="block p-3 bg-amber-50 rounded-lg hover:bg-amber-100 transition text-sm font-medium text-amber-700">My Assessments</a>
									<a href="index.php?page=candidate/profile" class="block p-3 bg-emerald-50 rounded-lg hover:bg-emerald-100 transition text-sm font-medium text-emerald-700">Edit Profile</a>
										</div></div>
										<div class="bg-white rounded-xl border p-6"><h3 class="font-semibold mb-4">Application Status</h3>
												<?php foreach(array_slice($apps,0,5) as $a):?><div class="p-3 bg-slate-50 rounded-lg mb-2 flex justify-between"><div><p class="font-medium text-sm"><?=htmlspecialchars($a["job_title"])?></p><p class="text-xs text-slate-500"><?=date("M j",strtotime($a["applied_at"]))?></p></div><span class="px-2 py-0.5 rounded-full text-xs font-semibold <?=$a["stage"]==="hired"?"bg-emerald-100 text-emerald-700":($a["stage"]==="rejected"?"bg-red-100 text-red-700":"bg-indigo-100 text-indigo-700")?>"><?=ucfirst(str_replace("_"," ",$a["stage"]))?></span></div><?php endforeach;
		if(empty($apps)):?><p class="text-slate-500 text-center py-4">No applications yet</p><?php endif;
		?></div>
		</div>
		<?php $content=ob_get_clean();
		$this->renderLayout($content,compact("pageTitle"));
	}
}