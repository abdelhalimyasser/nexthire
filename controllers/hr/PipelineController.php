<?php 
declare(strict_types=1);

class PipelineController extends BaseController {
    
	public function index(): void {
		$this->requireRole("hr_admin");
		$appModel=new ApplicationModel();
		$stages=["applied","screening","technical_test","interview","offer","hired","rejected"];
		$pipeline=[];
		foreach($stages as $s) {
			$apps=$appModel->findByStage($s);
			$pipeline[$s]=$apps;
		}
		$pageTitle="Pipeline Kanban";
		ob_start();
		?>
		<h1 class="text-2xl font-bold mb-6">Recruitment Pipeline</h1>
			<div class="flex gap-4 overflow-x-auto pb-4" id="kanban">
				<?php foreach($pipeline as $stage=>$apps): if($stage==="rejected")continue;
		?>
		<div class="min-w-[280px] bg-slate-100 rounded-xl p-4" data-stage="<?=$stage?>" ondragover="event.preventDefault()" ondrop="handleDrop(event,'<?=$stage?>')">
			<h3 class="font-semibold text-sm text-slate-600 mb-3 uppercase tracking-wider"><?=ucfirst(str_replace("_"," ",$stage))?> <span class="bg-white px-2 py-0.5 rounded-full text-xs"><?=count($apps)?></span></h3>
					<div class="space-y-2">
						<?php foreach($apps as $a): $det=$appModel->getWithDetails((int)$a["id"]);
		?>
		<div class="bg-white rounded-lg p-3 border shadow-sm cursor-move card-hover" draggable="true" ondragstart="handleDrag(event,<?=$a["id"]?>)" id="card-<?=$a["id"]?>">
			<p class="font-medium text-sm"><?=htmlspecialchars($det["candidate_name"]??"")?></p>
				<p class="text-xs text-slate-500"><?=htmlspecialchars($det["job_title"]??"")?></p>
					<?php if($a["match_score"]):?><div class="mt-2 flex items-center gap-2"><div class="flex-1 bg-slate-200 rounded-full h-1.5"><div class="bg-indigo-500 h-1.5 rounded-full" style="width:<?=$a["match_score"]?>%"></div></div><span class="text-xs text-indigo-600 font-medium"><?=round((float)$a["match_score"])?>%</span></div><?php endif;
		?>
		<a href="index.php?page=hr/pipeline&action=view&id=<?=$a["id"]?>" class="text-xs text-indigo-600 hover:underline mt-2 inline-block">Details →</a>
			</div>
			<?php endforeach;
		?></div>
		</div>
		<?php endforeach;
		?></div>
		<script>
		let dragId=null;
		function handleDrag(e,id) {
			dragId=id;
			e.dataTransfer.effectAllowed="move";
		}
		function handleDrop(e,stage) {
			e.preventDefault();
			if(!dragId)return;
fetch("index.php?page=hr/pipeline&action=transition&id="+dragId+"&to="+stage, {method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:"<?=CSRF_TOKEN_NAME?>=<?=$this->generateCsrf()?>"})
			.then(r=>r.json()).then(d=> {
				if(d.success)location.reload();
				else alert(d.error||"Cannot transition");
			});
			dragId=null;
		}
		</script>
		<?php $content=ob_get_clean();
		$this->renderLayout($content,compact("pageTitle"));
	}

	public function view(): void {
		$this->requireRole("hr_admin");
		$id=$this->getIntInput("id");
		$appModel=new ApplicationModel();
		$app=$appModel->getWithDetails($id);
		if(!$app) {
			$this->setFlash("error","Application not found");
			$this->redirect("index.php?page=hr/pipeline");
			return;
		}
		$stageLog=$appModel->getStageLog($id);
		$triage=new ScreeningTriageService();
		$allowed=$triage->getAllowedTransitions($id);
		$pageTitle="Application #".$id;
		ob_start();
		?>
		<div class="max-w-4xl mx-auto">
			<div class="bg-white rounded-xl border p-6 mb-6">
				<h2 class="text-xl font-bold"><?=htmlspecialchars($app["candidate_name"])?></h2>
					<p class="text-slate-500"><?=htmlspecialchars($app["job_title"])?> · <?=$app["department"]?></p>
						<div class="mt-4 flex gap-2"><?php foreach($allowed as $st):?>
								<a href="index.php?page=hr/pipeline&action=move&id=<?=$id?>&to=<?=$st?>" class="px-3 py-1 bg-indigo-600 text-white text-xs rounded-lg hover:bg-indigo-700"><?=ucfirst(str_replace("_"," ",$st))?></a>
									<?php endforeach;
		?></div>
		</div>
		<div class="bg-white rounded-xl border p-6"><h3 class="font-semibold mb-4">Stage History</h3>
				<div class="space-y-3"><?php foreach($stageLog as $log):?>
						<div class="flex items-center gap-4 text-sm"><div class="w-2 h-2 rounded-full bg-indigo-500"></div><span class="font-medium"><?=ucfirst($log["from_stage"]??"Start")?> → <?=ucfirst($log["to_stage"])?></span><span class="text-slate-400">by <?=htmlspecialchars($log["actor_name"])?></span><span class="text-slate-400"><?=$log["changed_at"]?></span></div>
											<?php endforeach;
		?></div></div>
		</div>
		<?php $content=ob_get_clean();
		$this->renderLayout($content,compact("pageTitle"));
	}

	public function move(): void {
		$this->requireRole("hr_admin");
		$id=$this->getIntInput("id");
		$to=$this->getInput("to");
		try {
			$svc=new ScreeningTriageService();
			$svc->transition($id,$to,(int)$this->currentUser["id"]);
			$this->setFlash("success","Moved to ".ucfirst(str_replace("_"," ",$to)));
		} catch(\Exception $e) {
			$this->setFlash("error",$e->getMessage());
		}
		$this->redirect("index.php?page=hr/pipeline&action=view&id=$id");
	}

	public function transition(): void {
		$this->requireRole("hr_admin");
		$id=$this->getIntInput("id");
		$to=$_GET["to"]??"";
		try {
			$svc=new ScreeningTriageService();
			$svc->transition($id,$to,(int)$this->currentUser["id"]);
			$this->jsonResponse(["success"=>true]);
		} catch(\Exception $e) {
			$this->jsonResponse(["success"=>false,"error"=>$e->getMessage()],400);
		}
	}
}