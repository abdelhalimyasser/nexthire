<?php 
declare(strict_types=1);

class AdminController extends BaseController {
    public function index(): void {
        $this->requireRole("hr_admin"); $users=(new UserModel())->findAll("created_at","DESC",50);
        $pageTitle="System Admin"; ob_start(); ?>
        <h1 class="text-2xl font-bold mb-6">System Administration</h1>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl border p-6"><h3 class="font-semibold mb-4">Users</h3>
            <table class="w-full text-sm"><thead><tr class="border-b text-left text-slate-500"><th class="pb-2">Name</th><th class="pb-2">Email</th><th class="pb-2">Role</th><th class="pb-2">Active</th></tr></thead><tbody>
            <?php foreach($users as $u):?><tr class="border-b border-slate-50"><td class="py-2 font-medium"><?=htmlspecialchars($u["name"])?></td><td class="text-slate-600"><?=htmlspecialchars($u["email"])?></td><td><span class="px-2 py-0.5 rounded-full text-xs <?=$u["role"]==="hr_admin"?"bg-purple-100 text-purple-700":($u["role"]==="interviewer"?"bg-blue-100 text-blue-700":($u["role"]==="dept_manager"?"bg-amber-100 text-amber-700":($u["role"]==="shadow"?"bg-slate-100 text-slate-700":"bg-emerald-100 text-emerald-700")))?>"><?=ucfirst(str_replace("_"," ",$u["role"]))?></span></td><td class="text-center"><?=$u["is_active"]?"<span class=\"text-emerald-600\">Yes</span>":"<span class=\"text-red-600\">No</span>"?></td></tr>
            <?php endforeach;?></tbody></table></div>

            <div class="bg-white rounded-xl border p-6"><h3 class="font-semibold mb-4">Quick Actions</h3>
            <div class="space-y-3">
                <a href="index.php?page=hr/admin&action=escalate" class="block p-4 bg-amber-50 rounded-lg hover:bg-amber-100 transition"><p class="font-medium text-amber-700">Check Pending Feedback</p><p class="text-xs text-amber-600">Send reminders and escalate overdue feedback</p></a>
                <a href="index.php?page=hr/admin&action=referrals" class="block p-4 bg-emerald-50 rounded-lg hover:bg-emerald-100 transition"><p class="font-medium text-emerald-700">Process Referral Rewards</p><p class="text-xs text-emerald-600">Check and process due referral bonuses</p></a>
                <a href="index.php?page=hr/admin&action=templates" class="block p-4 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition"><p class="font-medium text-indigo-700">Template Manager</p><p class="text-xs text-indigo-600">Manage job description and rubric templates</p></a>
                <a href="index.php?page=hr/admin&action=invites" class="block p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition"><p class="font-medium text-purple-700">Generate Invite Links</p><p class="text-xs text-purple-600">Create private registration links for staff</p></a>
            </div></div>
        </div>
        <?php $content=ob_get_clean(); $this->renderLayout($content,compact("pageTitle"));
    }

    public function invites(): void {
        $this->requireRole("hr_admin");
        $db=Database::getInstance(); $tokens=$db->query("SELECT it.*, u.name as creator FROM invite_tokens it JOIN users u ON it.created_by=u.id ORDER BY it.created_at DESC LIMIT 20")->fetchAll();
        $pageTitle="Invite Links"; ob_start(); ?>
        <div class="max-w-3xl mx-auto">
            <h2 class="text-xl font-bold mb-6">Private Registration Links</h2>
            <div class="bg-white rounded-xl border p-6 mb-6"><h3 class="font-semibold mb-4">Generate New Invite</h3>
            <div class="flex gap-3"><select id="inviteRole" class="px-4 py-2 border rounded-lg"><option value="interviewer">Interviewer</option><option value="dept_manager">Department Manager</option><option value="shadow">Shadow Interviewer</option><option value="hr_admin">HR Admin</option></select>
            <button onclick="generateInvite()" class="px-6 py-2 bg-indigo-600 text-white rounded-lg font-medium" id="genBtn">Generate Link</button></div>
            <div id="inviteResult" class="mt-4 hidden"><div class="p-4 bg-emerald-50 rounded-lg"><p class="text-sm text-emerald-700 mb-2">Invite link generated:</p><input id="inviteLink" class="w-full px-3 py-2 border rounded bg-white text-sm" readonly><button onclick="navigator.clipboard.writeText(document.getElementById('inviteLink').value)" class="mt-2 px-4 py-1 bg-indigo-600 text-white text-sm rounded">Copy Link</button></div></div></div>
            <div class="bg-white rounded-xl border p-6"><h3 class="font-semibold mb-4">Recent Invites</h3>
            <table class="w-full text-sm"><thead><tr class="border-b text-left text-slate-500"><th class="pb-2">Role</th><th class="pb-2">Created By</th><th class="pb-2">Status</th><th class="pb-2">Expires</th></tr></thead><tbody>
            <?php foreach($tokens as $t):?><tr class="border-b border-slate-50"><td class="py-2"><?=ucfirst(str_replace("_"," ",$t["target_role"]))?></td><td><?=htmlspecialchars($t["creator"])?></td><td><?=$t["used_by"]?"<span class=\"text-emerald-600\">Used</span>":($t["expires_at"]<date("Y-m-d H:i:s")?"<span class=\"text-red-600\">Expired</span>":"<span class=\"text-blue-600\">Active</span>")?></td><td class="text-slate-400"><?=date("M j",strtotime($t["expires_at"]))?></td></tr>
            <?php endforeach;?></tbody></table></div>
        </div>
        <script>
        function generateInvite(){
            const role=document.getElementById("inviteRole").value;
            const btn=document.getElementById("genBtn"); btn.textContent="Generating..."; btn.disabled=true;
            fetch("index.php?page=auth&action=generate_invite",{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:"target_role="+role+"&<?=CSRF_TOKEN_NAME?>=<?=$this->generateCsrf()?>"})
            .then(r=>r.json()).then(d=>{btn.textContent="Generate Link";btn.disabled=false;
                if(d.success){document.getElementById("inviteLink").value=d.link;document.getElementById("inviteResult").classList.remove("hidden");}
                else alert(d.error||"Failed");}).catch(()=>{btn.textContent="Generate Link";btn.disabled=false;});
        }
        </script>
        <?php $content=ob_get_clean(); $this->renderLayout($content,compact("pageTitle"));
    }

    public function escalate(): void {
        $this->requireRole("hr_admin");
        $results=(new NotificationEscalatorService())->checkPendingFeedback();
        $this->setFlash("success","Processed ".count($results)." escalations"); $this->redirect("index.php?page=hr/admin");
    }
    public function referrals(): void {
        $this->requireRole("hr_admin");
        $results=(new ReferralService())->processDueRewards();
        $this->setFlash("success","Processed ".count($results)." referral rewards"); $this->redirect("index.php?page=hr/admin");
    }
    public function templates(): void {
        $this->requireRole("hr_admin"); $tvs=new TemplateVersioningService();
        if($_SERVER["REQUEST_METHOD"]==="POST"&&$this->validateCsrf()){$tvs->createVersion($this->getInput("type"),$_POST["content"]??"",(int)$this->currentUser["id"]);$this->setFlash("success","Template version created");$this->redirect("index.php?page=hr/admin&action=templates");return;}
        $desc=$tvs->getActive("description"); $rubric=$tvs->getActive("rubric");
        $pageTitle="Template Manager"; ob_start(); ?>
        <div class="max-w-3xl mx-auto"><h2 class="text-xl font-bold mb-6">Template Versioning</h2>
        <div class="bg-white rounded-xl border p-6 mb-6"><h3 class="font-semibold mb-2">Active Description Template</h3><p class="text-sm text-slate-500 mb-2">Version: <?=$desc["version"]??0?></p>
        <pre class="bg-slate-50 p-4 rounded text-sm overflow-auto max-h-40"><?=htmlspecialchars($desc["content"]??"No template")?></pre></div>
        <div class="bg-white rounded-xl border p-6"><h3 class="font-semibold mb-4">Create New Version</h3>
        <form method="POST" class="space-y-4"><?=$this->csrfField()?><select name="type" class="px-4 py-2 border rounded-lg"><option value="description">Description</option><option value="rubric">Rubric</option></select>
        <textarea name="content" rows="6" class="w-full px-4 py-2 border rounded-lg" placeholder="Template content..."></textarea><button class="px-6 py-2 bg-indigo-600 text-white rounded-lg">Create Version</button></form></div></div>
        <?php $content=ob_get_clean(); $this->renderLayout($content,compact("pageTitle"));
    }
}