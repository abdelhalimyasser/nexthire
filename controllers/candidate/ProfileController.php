<?php 
declare(strict_types=1);

class ProfileController extends BaseController {
	public function index(): void {
		$this->requireRole("candidate");
		$user=$this->currentUser;
		$skills=json_decode($user["specializations"]??"[]",true)?:[];
		if($_SERVER["REQUEST_METHOD"]==="POST"&&$this->validateCsrf()) {
			$um=new UserModel();
			$data=["name"=>$this->getInput("name"),"department"=>$this->getInput("department"),"specializations"=>json_encode(array_filter(array_map("trim",explode(",",$_POST["skills"]??""))))];
			$um->update((int)$user["id"],$data);
			AuditLogger::getInstance()->log((int)$user["id"],"user",(int)$user["id"],"profile_updated",[],[$data]);
			$this->setFlash("success","Profile updated");
			$this->redirect("index.php?page=candidate/profile");
			return;
		}
		$pageTitle="My Profile";
		ob_start();
		?>
		<div class="max-w-2xl mx-auto">
			<div class="bg-white rounded-xl border p-8">
				<div class="flex items-center gap-4 mb-6"><div class="w-16 h-16 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white text-2xl font-bold"><?=strtoupper(substr($user["name"],0,1))?></div>
						<div><h2 class="text-xl font-bold"><?=htmlspecialchars($user["name"])?></h2><p class="text-slate-500"><?=htmlspecialchars($user["email"])?></p></div></div>
								<form method="POST" class="space-y-4"><?=$this->csrfField()?>
									<div><label class="block text-sm font-medium mb-1">Full Name</label><input name="name" value="<?=htmlspecialchars($user["name"])?>" class="w-full px-4 py-2 border rounded-lg"></div>
											<div><label class="block text-sm font-medium mb-1">Department / Field</label><input name="department" value="<?=htmlspecialchars($user["department"]??"")?>" class="w-full px-4 py-2 border rounded-lg"></div>
													<div><label class="block text-sm font-medium mb-1">Skills (comma-separated)</label><input name="skills" value="<?=htmlspecialchars(implode(", ",$skills))?>" class="w-full px-4 py-2 border rounded-lg" placeholder="PHP, JavaScript, MySQL"></div>
															<button class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-medium">Save Profile</button>
																</form>
																</div>
																</div>
																<?php $content=ob_get_clean();
		$this->renderLayout($content,compact("pageTitle"));
	}
}