<?php declare(strict_types=1);
class PanelController extends BaseController {
    public function index(): void {
        $this->requireRole("interviewer","hr_admin");
        $db=Database::getInstance();
        $stmt=$db->prepare("SELECT ip.*, j.title as job_title FROM interview_panels ip JOIN job_requisitions j ON ip.job_id=j.id JOIN panel_members pm ON pm.panel_id=ip.id WHERE pm.user_id=:uid ORDER BY ip.scheduled_at DESC");
        $stmt->execute(["uid"=>$this->currentUser["id"]]); $panels=$stmt->fetchAll();
        $pageTitle="My Panels"; ob_start(); ?>
        <h1 class="text-2xl font-bold mb-6">Interview Panels</h1>
        <div class="bg-white rounded-xl border"><table class="w-full text-sm"><thead><tr class="border-b text-left text-slate-500"><th class="p-4">Panel</th><th class="p-4">Job</th><th class="p-4">Scheduled</th><th class="p-4">Duration</th><th class="p-4">Status</th><th class="p-4">Actions</th></tr></thead><tbody>
        <?php foreach($panels as $p):?><tr class="border-b hover:bg-slate-50"><td class="p-4 font-medium">#<?=$p["id"]?></td><td class="p-4"><?=htmlspecialchars($p["job_title"])?></td><td class="p-4"><?=$p["scheduled_at"]?></td><td class="p-4"><?=$p["duration_minutes"]+$p["extended_by_minutes"]?>min</td>
        <td class="p-4"><span class="px-2 py-0.5 rounded-full text-xs font-semibold <?=$p["status"]==="completed"?"bg-emerald-100 text-emerald-700":"bg-blue-100 text-blue-700"?>"><?=ucfirst($p["status"])?></span></td>
        <td class="p-4"><a href="index.php?page=interviewer/feedback&action=gap&id=<?=$p["application_id"]?>" class="text-purple-600 hover:underline text-xs">Gap Analysis</a></td></tr>
        <?php endforeach;?></tbody></table></div>
        <?php $content=ob_get_clean(); $this->renderLayout($content,compact("pageTitle"));
    }
}