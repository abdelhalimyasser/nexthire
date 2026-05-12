<?php 
declare(strict_types=1);

class AuthController extends BaseController {
    
	public function index(): void { $this->login(); }

		public function login(): void {
		if ($this->currentUser) {
			$this->redirect("index.php?page=dashboard");
			return;
		}
		$error = "";
		if ($_SERVER["REQUEST_METHOD"] === "POST") {
			if (!$this->validateCsrf()) {
				$error = "Invalid CSRF token";
			}
			else {
				$email = filter_input(INPUT_POST, "email", FILTER_SANITIZE_EMAIL);
				$password = $_POST["password"] ?? "";
				$userModel = new UserModel();
				$user = $userModel->authenticate($email, $password);
				if ($user) {
					session_regenerate_id(true);
					$_SESSION["user_id"] = $user["id"];
					AuditLogger::getInstance()->log((int)$user["id"], "user", (int)$user["id"], "login", [], []);
					$this->redirect("index.php?page=dashboard");
					return;
				}
				$error = "Invalid email or password";
			}
		}
		$csrf = $this->csrfField();
		?>
		<!DOCTYPE html><html lang="en" class="h-full"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Login - NextHire</title>
			                                     <script src="https://cdn.tailwindcss.com"></script><link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
			                                             <style>*{font-family:"Inter",sans-serif;}</style></head>
		<body class="h-full"><div class="min-h-full flex">
				                                <div class="hidden lg:flex lg:w-1/2 items-center justify-center p-12" style="background:linear-gradient(135deg,#0f172a 0%,#1e293b 50%,#334155 100%)">
					                                        <div class="text-center"><div class="w-20 h-20 mx-auto mb-8 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center"><svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg></div>
								                                                <h1 class="text-4xl font-bold text-white mb-4">NextHire</h1><p class="text-slate-400 text-lg max-w-md">AI-Driven Smart Recruitment & Interview Management System</p>
										                                                        <div class="mt-12 grid grid-cols-3 gap-6 text-center"><div><p class="text-3xl font-bold text-indigo-400">42</p><p class="text-sm text-slate-500">Smart Features</p></div><div><p class="text-3xl font-bold text-purple-400">5</p><p class="text-sm text-slate-500">Role Portals</p></div><div><p class="text-3xl font-bold text-blue-400">100%</p><p class="text-sm text-slate-500">Automated</p></div></div></div>
																	                                                                </div>
																	                                                                <div class="flex-1 flex items-center justify-center p-8 bg-slate-50">
																		                                                                        <div class="w-full max-w-md">
																			                                                                                <h2 class="text-3xl font-bold text-slate-800 mb-2">Welcome back</h2>
																				                                                                                        <p class="text-slate-500 mb-8">Sign in to your account to continue</p>
																					                                                                                                <?php if ($error): ?><div class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg border border-red-200 text-sm"><?= htmlspecialchars($error) ?></div><?php endif;
		?>
		<form method="POST" action="index.php?page=auth&action=login" class="space-y-5">
			                           <?= $csrf ?>
			                               <div><label class="block text-sm font-medium text-slate-700 mb-1">Email</label><input type="email" name="email" required class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition" placeholder="you@company.com"></div>
					                                       <div><label class="block text-sm font-medium text-slate-700 mb-1">Password</label><input type="password" name="password" required class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition" placeholder="Enter your password"></div>
							                                               <button type="submit" class="w-full py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg font-semibold hover:from-indigo-700 hover:to-purple-700 transition shadow-lg shadow-indigo-200">Sign In</button>
								                                                       </form>
								                                                       <p class="mt-6 text-center text-sm text-slate-500">Candidate? <a href="index.php?page=auth&action=register" class="text-indigo-600 font-medium hover:underline">Create an account</a></p>
										                                                               <div class="mt-6 p-4 bg-white rounded-lg border border-slate-200">
											                                                                       <p class="text-xs font-semibold text-slate-500 mb-2">Demo Credentials:</p>
												                                                                               <div class="space-y-1 text-xs text-slate-600">
													                                                                                       <p>HR Admin: <strong>hr1@nexthire.com</strong> / password123</p>
													                                                                                       <p>Dept Manager: <strong>dm1@nexthire.com</strong> / password123</p>
													                                                                                       <p>Interviewer: <strong>iv1@nexthire.com</strong> / password123</p>
													                                                                                       <p>Shadow: <strong>sh1@nexthire.com</strong> / password123</p>
													                                                                                       <p>Candidate: <strong>c1@nexthire.com</strong> / password123</p>
													                                                                                       </div>
													                                                                                       </div>
													                                                                                       </div>
													                                                                                       </div>
													                                                                                       </div></body></html>
													                                                                                       <?php
												}

	public function register(): void {
		if ($this->currentUser) {
			$this->redirect("index.php?page=dashboard");
			return;
		}
		$error = "";
		if ($_SERVER["REQUEST_METHOD"] === "POST" && $this->validateCsrf()) {
			$name    = $this->getInput("name");
			$email   = filter_input(INPUT_POST, "email", FILTER_SANITIZE_EMAIL);
			$password = $_POST["password"] ?? "";
			$confirm  = $_POST["confirm_password"] ?? "";

			// Validation
			if (strlen($name) < 2)                   $error = "Name must be at least 2 characters";
			elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = "Invalid email address";
			elseif (strlen($password) < 8)            $error = "Password must be at least 8 characters";
			elseif ($password !== $confirm)            $error = "Passwords do not match";
			else {
				$um = new UserModel();
				if ($um->findByEmail($email)) {
					$error = "Email already registered";
				} else {
					// Handle CV upload
					$cvPath = null;
					if (!empty($_FILES["cv"]["name"])) {
						$file = $_FILES["cv"];
						$ext  = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
						if ($ext !== "pdf")                    $error = "CV must be a PDF file";
						elseif ($file["size"] > MAX_CV_SIZE)   $error = "CV must be under 5MB";
						else {
							if (!is_dir(CV_UPLOAD_DIR)) mkdir(CV_UPLOAD_DIR, 0777, true);
							$filename = uniqid("cv_", true) . ".pdf";
							$dest = CV_UPLOAD_DIR . "/" . $filename;
							if (move_uploaded_file($file["tmp_name"], $dest)) {
								$cvPath = "uploads/cvs/" . $filename;
							} else {
								$error = "CV upload failed. Please try again.";
							}
						}
					}

					if (empty($error)) {
						// Document links
						$docLinks = [
						                "github"     => filter_input(INPUT_POST, "github",    FILTER_SANITIZE_URL) ?: null,
						                "linkedin"   => filter_input(INPUT_POST, "linkedin",  FILTER_SANITIZE_URL) ?: null,
						                "drive"      => filter_input(INPUT_POST, "drive",     FILTER_SANITIZE_URL) ?: null,
						                "portfolio"  => filter_input(INPUT_POST, "portfolio", FILTER_SANITIZE_URL) ?: null,
						            ];
						$docLinks = array_filter($docLinks);

						$id = $um->create([
						                      "name"           => $name,
						                      "email"          => $email,
						                      "password_hash"  => password_hash($password, PASSWORD_DEFAULT),
						                      "role"           => "candidate",
						                      "cv_path"        => $cvPath,
						                      "document_links" => !empty($docLinks) ? json_encode($docLinks) : null,
						                  ]);
						AuditLogger::getInstance()->log($id, "user", $id, "registered", [], ["role"=>"candidate"]);
						EmailService::getInstance()->sendTemplate(
						    $email, "Welcome to NextHire", "account_created",
						    ["name"=>$name, "role"=>"Candidate", "link"=>BASE_URL."/index.php?page=auth&action=login"]
						);
						$this->setFlash("success", "Registration successful! Please log in.");
						$this->redirect("index.php?page=auth&action=login");
						return;
					}
				}
			}
		}
		$csrf = $this->csrfField();
		?>
		<!DOCTYPE html><html lang="en" class="h-full"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Register - NextHire</title>
			                                     <script src="https://cdn.tailwindcss.com"></script><link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
			                                             <style>*{font-family:"Inter",sans-serif;}</style></head>
		<body class="min-h-screen bg-slate-50 flex items-center justify-center p-6">
			            <div class="w-full max-w-lg">
				                       <div class="text-center mb-8">
					                                  <div class="w-14 h-14 mx-auto mb-4 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
						                                          <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
							                                                  </div>
							                                                  <h1 class="text-2xl font-bold text-slate-800">Create Candidate Account</h1>
								                                                          <p class="text-slate-500 text-sm mt-1">Join NextHire to find your next opportunity</p>
									                                                                  </div>
									                                                                  <?php if ($error): ?><div class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg border border-red-200 text-sm"><?= htmlspecialchars($error) ?></div><?php endif;
		?>
		<form method="POST" enctype="multipart/form-data" class="bg-white rounded-xl border p-6 space-y-4 shadow-sm">
			                            <?= $csrf ?>
			                                <div class="grid grid-cols-1 gap-4">
				                                        <div>
				                                        <label class="block text-sm font-medium text-slate-700 mb-1">Full Name <span class="text-red-500">*</span></label>
						                                                <input type="text" name="name" required minlength="2" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none transition" placeholder="John Doe">
							                                                        </div>
							                                                        <div>
							                                                        <label class="block text-sm font-medium text-slate-700 mb-1">Email Address <span class="text-red-500">*</span></label>
									                                                                <input type="email" name="email" required class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none transition" placeholder="you@email.com">
										                                                                        </div>
										                                                                        <div class="grid grid-cols-2 gap-3">
											                                                                                <div>
											                                                                                <label class="block text-sm font-medium text-slate-700 mb-1">Password <span class="text-red-500">*</span></label>
													                                                                                        <input type="password" name="password" required minlength="8" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none transition" placeholder="Min 8 chars">
														                                                                                                </div>
														                                                                                                <div>
														                                                                                                <label class="block text-sm font-medium text-slate-700 mb-1">Confirm Password <span class="text-red-500">*</span></label>
																                                                                                                        <input type="password" name="confirm_password" required class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none transition" placeholder="Repeat">
																	                                                                                                                </div>
																	                                                                                                                </div>

																	                                                                                                                <!-- CV Upload -->
																	                                                                                                                <div>
																	                                                                                                                <label class="block text-sm font-medium text-slate-700 mb-1">CV / Resume (PDF only, max 5MB)</label>
																		                                                                                                                        <div class="border-2 border-dashed border-slate-200 rounded-lg p-4 text-center hover:border-indigo-400 transition" id="cvDropZone">
																			                                                                                                                                <svg class="w-8 h-8 mx-auto text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
																				                                                                                                                                        <p class="text-sm text-slate-500" id="cvLabel">Click to upload or drag &amp;
		drop your CV</p>
		<input type="file" name="cv" id="cvInput" accept=".pdf" class="hidden" onchange="updateCvLabel(this)">
			                                </div>
			                                <button type="button" onclick="document.getElementById('cvInput').click()" class="mt-2 text-sm text-indigo-600 hover:underline">Select PDF file</button>
				                                        </div>

				                                        <!-- Document Links -->
				                                        <div>
				                                        <label class="block text-sm font-medium text-slate-700 mb-2">Document Links (optional)</label>
					                                                <div class="space-y-2">
						                                                        <div class="flex items-center gap-2">
							                                                                <span class="w-20 text-xs text-slate-500 font-medium">GitHub</span>
								                                                                        <input type="url" name="github" class="flex-1 px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="https://github.com/username">
									                                                                                </div>
									                                                                                <div class="flex items-center gap-2">
										                                                                                        <span class="w-20 text-xs text-slate-500 font-medium">LinkedIn</span>
											                                                                                                <input type="url" name="linkedin" class="flex-1 px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="https://linkedin.com/in/username">
												                                                                                                        </div>
												                                                                                                        <div class="flex items-center gap-2">
													                                                                                                                <span class="w-20 text-xs text-slate-500 font-medium">Drive</span>
														                                                                                                                        <input type="url" name="drive" class="flex-1 px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="Google Drive link">
															                                                                                                                                </div>
															                                                                                                                                <div class="flex items-center gap-2">
																                                                                                                                                        <span class="w-20 text-xs text-slate-500 font-medium">Portfolio</span>
																	                                                                                                                                                <input type="url" name="portfolio" class="flex-1 px-3 py-2 rounded-lg border border-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="https://yourportfolio.com">
																		                                                                                                                                                        </div>
																		                                                                                                                                                        </div>
																		                                                                                                                                                        </div>
																		                                                                                                                                                        </div>

																		                                                                                                                                                        <button type="submit" class="w-full py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg font-semibold hover:from-indigo-700 hover:to-purple-700 transition shadow-lg shadow-indigo-200">
																			                                                                                                                                                                Create Account
																			                                                                                                                                                                </button>
																			                                                                                                                                                                </form>
																			                                                                                                                                                                <p class="mt-4 text-center text-sm text-slate-500">Already have an account? <a href="index.php?page=auth&action=login" class="text-indigo-600 font-medium hover:underline">Sign in</a></p>
																					                                                                                                                                                                        </div>
																					                                                                                                                                                                        <script>
																					                                                                                                                                                                        function updateCvLabel(input) {
																					const label = document.getElementById('cvLabel');
																					if (input.files && input.files[0]) {
																						label.textContent = input.files[0].name + ' selected';
																						label.classList.add('text-indigo-600', 'font-medium');
																					}
																				}
		document.getElementById('cvDropZone').addEventListener('click', function() {
			document.getElementById('cvInput').click();
		});
		document.getElementById('cvDropZone').addEventListener('dragover', function(e) {
			e.preventDefault();
			this.classList.add('border-indigo-400', 'bg-indigo-50');
		});
		document.getElementById('cvDropZone').addEventListener('drop', function(e) {
			e.preventDefault();
			this.classList.remove('border-indigo-400','bg-indigo-50');
			const dt = e.dataTransfer;
			if (dt.files.length) {
				document.getElementById('cvInput').files = dt.files;
				updateCvLabel(document.getElementById('cvInput'));
			}
		});
		</script>
		</body></html>
		<?php
	}


	public function invite(): void {
		$token = $this->getInput("token");
		if (empty($token)) {
			$this->setFlash("error","Invalid invite link");
			$this->redirect("index.php?page=auth&action=login");
			return;
		}
		$db = Database::getInstance();
		$stmt = $db->prepare("SELECT * FROM invite_tokens WHERE token=:t AND used_by IS NULL AND expires_at > NOW()");
		$stmt->execute(["t"=>$token]);
		$invite = $stmt->fetch();
		if (!$invite) {
			$this->setFlash("error","Invite link is invalid or expired");
			$this->redirect("index.php?page=auth&action=login");
			return;
		}

		$error = "";
		if ($_SERVER["REQUEST_METHOD"] === "POST" && $this->validateCsrf()) {
			$name = $this->getInput("name");
			$email = filter_input(INPUT_POST,"email",FILTER_SANITIZE_EMAIL);
			$password = $_POST["password"] ?? "";
			$department = $this->getInput("department");
			if (strlen($password)<8) $error = "Password must be at least 8 characters";
			else {
				$um = new UserModel();
				if ($um->findByEmail($email)) {
					$error = "Email already registered";
				}
				else {
					$id = $um->create(["name"=>$name,"email"=>$email,"password_hash"=>password_hash($password,PASSWORD_DEFAULT),"role"=>$invite["target_role"],"department"=>$department]);
					$db->prepare("UPDATE invite_tokens SET used_by=:uid, used_at=NOW() WHERE id=:id")->execute(["uid"=>$id,"id"=>$invite["id"]]);
					AuditLogger::getInstance()->log($id,"user",$id,"registered_via_invite",[],["role"=>$invite["target_role"]]);
					EmailService::getInstance()->sendTemplate($email,"Welcome to NextHire","account_created",["name"=>$name,"role"=>ucfirst(str_replace("_"," ",$invite["target_role"])),"link"=>BASE_URL."/index.php?page=auth&action=login"]);
					$this->setFlash("success","Account created! Please log in.");
					$this->redirect("index.php?page=auth&action=login");
					return;
				}
			}
		}
		$role = ucfirst(str_replace("_"," ",$invite["target_role"]));
		$csrf = $this->csrfField();
		?>
		<!DOCTYPE html><html lang="en" class="h-full"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Staff Registration - NextHire</title>
			                                     <script src="https://cdn.tailwindcss.com"></script><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
			                                             <style>*{font-family:"Inter",sans-serif;}</style></head>
		<body class="h-full bg-slate-50 flex items-center justify-center p-8">
			            <div class="w-full max-w-md">
				                       <div class="text-center mb-8"><div class="w-14 h-14 mx-auto mb-4 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center"><svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg></div>
							                                  <h2 class="text-2xl font-bold text-slate-800">Staff Registration</h2><p class="text-slate-500 text-sm mt-1">You are registering as:
									                                          <strong class="text-indigo-600"><?= $role ?></strong></p></div>
										                                                  <?php if ($error): ?><div class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg border text-sm"><?= htmlspecialchars($error) ?></div><?php endif;
		?>
		<form method="POST" class="bg-white rounded-xl border p-6 space-y-4 shadow-sm">
			                          <?= $csrf ?><input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
			                                  <div><label class="block text-sm font-medium mb-1">Full Name</label><input name="name" required class="w-full px-4 py-2.5 rounded-lg border focus:ring-2 focus:ring-indigo-500 outline-none"></div>
					                                          <div><label class="block text-sm font-medium mb-1">Email</label><input type="email" name="email" required class="w-full px-4 py-2.5 rounded-lg border focus:ring-2 focus:ring-indigo-500 outline-none"></div>
							                                                  <div><label class="block text-sm font-medium mb-1">Department</label><input name="department" required class="w-full px-4 py-2.5 rounded-lg border focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="e.g. Engineering, HR"></div>
									                                                          <div><label class="block text-sm font-medium mb-1">Password</label><input type="password" name="password" required minlength="8" class="w-full px-4 py-2.5 rounded-lg border focus:ring-2 focus:ring-indigo-500 outline-none"></div>
											                                                                  <button type="submit" class="w-full py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg font-semibold">Create Staff Account</button>
												                                                                          </form>
												                                                                          </div></body></html>
												                                                                          <?php
											}

	public function generate_invite(): void {
		$this->requireRole("hr_admin");
		if ($_SERVER["REQUEST_METHOD"]==="POST" && $this->validateCsrf()) {
			$role = $this->getInput("target_role");
			$token = bin2hex(random_bytes(32));
			$validRoles = ["hr_admin","interviewer","dept_manager","shadow"];
			if (!in_array($role,$validRoles)) {
				$this->jsonResponse(["error"=>"Invalid role"],400);
				return;
			}
			$db = Database::getInstance();
			$db->prepare("INSERT INTO invite_tokens (token,target_role,created_by,expires_at) VALUES(:t,:r,:cb,DATE_ADD(NOW(),INTERVAL 7 DAY))")
			->execute(["t"=>$token,"r"=>$role,"cb"=>$this->currentUser["id"]]);
			$link = BASE_URL . "/index.php?page=auth&action=invite&token=" . $token;
			$this->jsonResponse(["success"=>true,"link"=>$link,"token"=>$token]);
			return;
		}
		$this->jsonResponse(["error"=>"POST required"],405);
	}

	public function logout(): void {
		if ($this->currentUser) AuditLogger::getInstance()->log((int)$this->currentUser["id"],"user",(int)$this->currentUser["id"],"logout",[],[]);
		session_destroy();
		$this->redirect("index.php?page=auth&action=login");
	}
}