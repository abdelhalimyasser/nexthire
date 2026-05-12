<?php 
declare(strict_types=1);

class Router {
	public static function dispatch(): void {
		$page   = filter_input(INPUT_GET, "page", FILTER_SANITIZE_SPECIAL_CHARS) ?? "dashboard";
		$action = filter_input(INPUT_GET, "action", FILTER_SANITIZE_SPECIAL_CHARS) ?? "index";

		// Redirect root to dashboard or login
		if (!$page) {
			$page = isset($_SESSION["user_id"]) ? "dashboard" : "auth";
			$action = isset($_SESSION["user_id"]) ? "index" : "login";
		}

		$routes = [
		    // Auth
		    "auth"                       => ["file"=>"controllers/AuthController.php","class"=>"AuthController"],
		    // Dashboard
		    "dashboard"                  => ["file"=>"controllers/DashboardController.php","class"=>"DashboardController"],
		    // HR Admin
		    "hr/jobs"                    => ["file"=>"controllers/hr/JobRequisitionController.php","class"=>"JobRequisitionController"],
		    "hr/pipeline"                => ["file"=>"controllers/hr/PipelineController.php","class"=>"PipelineController"],
		    "hr/shortlisting"            => ["file"=>"controllers/hr/ShortlistingController.php","class"=>"ShortlistingController"],
		    "hr/analytics"               => ["file"=>"controllers/hr/AnalyticsController.php","class"=>"AnalyticsController"],
		    "hr/offers"                  => ["file"=>"controllers/hr/OfferController.php","class"=>"OfferController"],
		    "hr/onboarding"              => ["file"=>"controllers/hr/OnboardingController.php","class"=>"OnboardingController"],
		    "hr/compliance"              => ["file"=>"controllers/hr/ComplianceController.php","class"=>"ComplianceController"],
		    "hr/admin"                   => ["file"=>"controllers/hr/AdminController.php","class"=>"AdminController"],
		    "hr/referrals"               => ["file"=>"controllers/hr/ReferralController.php","class"=>"ReferralController"],
		    "hr/interviews"              => ["file"=>"controllers/hr/InterviewController.php","class"=>"InterviewController"],
		    // Department Manager
		    "dept_manager"               => ["file"=>"controllers/hr/DeptManagerController.php","class"=>"DeptManagerController"],
		    // Interviewer
		    "interviewer/schedule"       => ["file"=>"controllers/interviewer/SchedulingController.php","class"=>"SchedulingController"],
		    "interviewer/live"           => ["file"=>"controllers/interviewer/LiveSessionController.php","class"=>"LiveSessionController"],
		    "interviewer/feedback"       => ["file"=>"controllers/interviewer/FeedbackController.php","class"=>"FeedbackController"],
		    "interviewer/panel"          => ["file"=>"controllers/interviewer/PanelController.php","class"=>"PanelController"],
		    "interviewer/questions"      => ["file"=>"controllers/interviewer/QuestionBankController.php","class"=>"QuestionBankController"],
		    // Candidate
		    "candidate/profile"          => ["file"=>"controllers/candidate/ProfileController.php","class"=>"ProfileController"],
		    "candidate/applications"     => ["file"=>"controllers/candidate/ApplicationController.php","class"=>"ApplicationController"],
		    "candidate/assessments"      => ["file"=>"controllers/candidate/AssessmentController.php","class"=>"AssessmentController"],
		    "candidate/interviews"       => ["file"=>"controllers/hr/InterviewController.php","class"=>"InterviewController"],
		    "candidate/interview"        => ["file"=>"controllers/interviewer/LiveSessionController.php","class"=>"LiveSessionController"],
		    // Shadow — can access schedule and live room
		    "shadow/schedule"            => ["file"=>"controllers/interviewer/SchedulingController.php","class"=>"SchedulingController"],
		    "shadow/live"                => ["file"=>"controllers/interviewer/LiveSessionController.php","class"=>"LiveSessionController"],
		];

		if (!isset($routes[$page])) {
			http_response_code(404);
			$f = __DIR__ . "/../views/partials/404.php";
			if (file_exists($f)) include $f;
			else echo "<div style='font-family:sans-serif;padding:40px;text-align:center'><h1>404 Not Found</h1><p>Page '$page' does not exist.</p><a href='index.php'>Go Home</a></div>";
			return;
		}

		$route = $routes[$page];
		$controllerFile = __DIR__ . "/../" . $route["file"];

		if (!file_exists($controllerFile)) {
			http_response_code(500);
			echo "<div class='p-8 text-red-500'>Controller not found: " . htmlspecialchars($route["class"]) . "</div>";
			return;
		}

		require_once $controllerFile;
		$className = $route["class"];
		if (!class_exists($className)) {
			http_response_code(500);
			echo "<div class='p-8 text-red-500'>Class not found: " . htmlspecialchars($className) . "</div>";
			return;
		}

		$controller = new $className();
		$method = preg_replace('/[^a-zA-Z0-9_]/', '', $action);
		if (!method_exists($controller, $method)) $method = "index";
		$controller->$method();
	}
}