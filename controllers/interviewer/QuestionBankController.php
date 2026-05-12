<?php declare(strict_types=1);
class QuestionBankController extends BaseController {
    public function index(): void {
        $this->requireRole(["interviewer","hr_admin"]);
        $db = Database::getInstance();
        $lang   = $this->getInput("lang","");
        $diff   = $this->getInput("diff","");
        $search = $this->getInput("q","");
        $where  = "WHERE 1=1";
        $params = [];
        if ($lang)   { $where .= " AND language=:lang";   $params["lang"]=$lang; }
        if ($diff)   { $where .= " AND difficulty=:diff";  $params["diff"]=$diff; }
        if ($search) { $where .= " AND content LIKE :q";   $params["q"]="%$search%"; }
        $questions = $db->prepare("SELECT * FROM questions $where ORDER BY created_at DESC LIMIT 100");
        $questions->execute($params); $questions=$questions->fetchAll();
        $langs = defined("CODING_LANGUAGE_LABELS")?CODING_LANGUAGE_LABELS:["javascript"=>"JavaScript","python"=>"Python"];
        $pageTitle = "Question Bank"; ob_start(); ?>
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Question Bank</h1>
            <button onclick="document.getElementById('qModal').classList.remove('hidden')" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg font-medium">Add Question</button>
        </div>
        <!-- Filter Bar -->
        <form method="GET" action="index.php" class="flex gap-3 mb-6">
            <input type="hidden" name="page" value="interviewer/questions">
            <input name="q" value="<?= htmlspecialchars($search) ?>" class="flex-1 px-4 py-2 border rounded-lg text-sm" placeholder="Search questions...">
            <select name="lang" class="px-3 py-2 border rounded-lg text-sm">
                <option value="">All Languages</option>
                <?php foreach($langs as $k=>$l): ?><option value="<?= $k ?>" <?= $lang===$k?"selected":"" ?>><?= $l ?></option><?php endforeach; ?>
            </select>
            <select name="diff" class="px-3 py-2 border rounded-lg text-sm">
                <option value="">All Difficulties</option>
                <option value="easy" <?= $diff==="easy"?"selected":"" ?>>Easy</option>
                <option value="medium" <?= $diff==="medium"?"selected":"" ?>>Medium</option>
                <option value="hard" <?= $diff==="hard"?"selected":"" ?>>Hard</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-slate-700 text-white rounded-lg text-sm">Filter</button>
        </form>

        <!-- Add Question Modal -->
        <div id="qModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl p-6 max-w-lg w-full shadow-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-4"><h3 class="font-bold text-lg" id="modalTitle">Add Question</h3><button onclick="closeModal()" class="text-slate-400 hover:text-slate-600">&#x2715;</button></div>
                <form id="qForm" class="space-y-3">
                    <input type="hidden" id="qId" name="id" value="">
                    <?= $this->csrfField() ?>
                    <div><label class="block text-sm font-medium mb-1">Question <span class="text-red-500">*</span></label><textarea name="content" id="qContent" rows="3" required class="w-full px-3 py-2 border rounded-lg text-sm"></textarea></div>
                    <div class="grid grid-cols-3 gap-3">
                        <div><label class="block text-sm font-medium mb-1">Type</label>
                            <select name="type" id="qType" class="w-full px-3 py-2 border rounded-lg text-sm">
                                <option value="text">Text</option><option value="coding">Coding</option><option value="mcq">MCQ</option>
                            </select>
                        </div>
                        <div><label class="block text-sm font-medium mb-1">Difficulty</label>
                            <select name="difficulty" id="qDiff" class="w-full px-3 py-2 border rounded-lg text-sm">
                                <option value="easy">Easy</option><option value="medium">Medium</option><option value="hard">Hard</option>
                            </select>
                        </div>
                        <div><label class="block text-sm font-medium mb-1">Language</label>
                            <select name="language" id="qLang" class="w-full px-3 py-2 border rounded-lg text-sm">
                                <option value="">Any</option>
                                <?php foreach($langs as $k=>$l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div><label class="block text-sm font-medium mb-1">Tags (comma-separated)</label><input name="tags" id="qTags" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="arrays, recursion, oop"></div>
                    <button type="submit" id="qSubmitBtn" class="w-full py-2 bg-indigo-600 text-white rounded-lg font-medium text-sm">Save Question</button>
                    <p id="qResult" class="text-center text-sm hidden"></p>
                </form>
            </div>
        </div>

        <!-- Questions List -->
        <div class="space-y-3">
            <?php foreach($questions as $q): $dc=["easy"=>"bg-emerald-100 text-emerald-700","medium"=>"bg-amber-100 text-amber-700","hard"=>"bg-red-100 text-red-700"]; ?>
            <div class="bg-white rounded-xl border p-4 card-hover">
                <div class="flex justify-between items-start gap-4">
                    <p class="text-sm text-slate-800 flex-1"><?= htmlspecialchars(substr($q["content"],0,200)) ?><?= strlen($q["content"])>200?"...":"" ?></p>
                    <div class="flex gap-1 flex-shrink-0">
                        <button onclick="editQuestion(<?= htmlspecialchars(json_encode($q)) ?>)" class="px-3 py-1 text-xs bg-slate-100 rounded hover:bg-slate-200">Edit</button>
                        <button onclick="deleteQuestion(<?= $q["id"] ?>)" class="px-3 py-1 text-xs bg-red-100 text-red-600 rounded hover:bg-red-200">Delete</button>
                    </div>
                </div>
                <div class="flex gap-2 mt-2">
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= $dc[$q["difficulty"]] ?>"><?= ucfirst($q["difficulty"]) ?></span>
                    <?php if($q["language"]): ?><span class="px-2 py-0.5 rounded-full text-xs bg-indigo-100 text-indigo-700"><?= $langs[$q["language"]] ?? $q["language"] ?></span><?php endif; ?>
                    <?php if($q["type"]): ?><span class="px-2 py-0.5 rounded-full text-xs bg-slate-100 text-slate-600"><?= ucfirst($q["type"]) ?></span><?php endif; ?>
                    <?php if($q["tags"]): ?><span class="text-xs text-slate-400"><?= htmlspecialchars($q["tags"]) ?></span><?php endif; ?>
                </div>
            </div>
            <?php endforeach; if(empty($questions)): ?>
            <div class="text-center py-12 bg-white rounded-xl border"><p class="text-slate-500">No questions found. Add your first question!</p></div>
            <?php endif; ?>
        </div>
        <script>
        const CSRF = <?= json_encode($this->generateCsrf()) ?>;
        function closeModal(){ document.getElementById("qModal").classList.add("hidden"); document.getElementById("qForm").reset(); document.getElementById("qId").value=""; document.getElementById("modalTitle").textContent="Add Question"; }
        function editQuestion(q){ document.getElementById("qModal").classList.remove("hidden"); document.getElementById("modalTitle").textContent="Edit Question"; document.getElementById("qId").value=q.id; document.getElementById("qContent").value=q.content; document.getElementById("qType").value=q.type; document.getElementById("qDiff").value=q.difficulty; document.getElementById("qLang").value=q.language||""; document.getElementById("qTags").value=q.tags||""; }
        async function deleteQuestion(id){
            if(!confirm("Delete this question?")) return;
            const r=await fetch("index.php?page=interviewer/questions&action=delete",{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:"id="+id+"&<?= CSRF_TOKEN_NAME ?>="+CSRF});
            const d=await r.json(); if(d.success)location.reload(); else alert(d.error||"Failed");
        }
        document.getElementById("qForm").addEventListener("submit", async function(e){
            e.preventDefault(); const btn=document.getElementById("qSubmitBtn"); btn.disabled=true; btn.textContent="Saving...";
            const action=document.getElementById("qId").value?"update":"create";
            const r=await fetch("index.php?page=interviewer/questions&action="+action,{method:"POST",body:new URLSearchParams(new FormData(this))});
            const d=await r.json(); btn.disabled=false; btn.textContent="Save Question";
            if(d.success){location.reload();}else{document.getElementById("qResult").classList.remove("hidden");document.getElementById("qResult").className="text-center text-sm text-red-600";document.getElementById("qResult").textContent=d.error||"Failed";}
        });
        </script>
        <?php $content = ob_get_clean(); $this->renderLayout($content, compact("pageTitle"));
    }

    public function create(): void {
        $this->requireRole(["interviewer","hr_admin"]);
        if (!$this->validateCsrf()) { $this->jsonResponse(["error"=>"Invalid token"],403); return; }
        $id = (new QuestionModel())->create(["content"=>$this->getInput("content"),"type"=>$this->getInput("type","text"),"difficulty"=>$this->getInput("difficulty","medium"),"language"=>$this->getInput("language")?:null,"tags"=>$this->getInput("tags")]);
        $this->jsonResponse(["success"=>true,"id"=>$id]);
    }

    public function update(): void {
        $this->requireRole(["interviewer","hr_admin"]);
        if (!$this->validateCsrf()) { $this->jsonResponse(["error"=>"Invalid token"],403); return; }
        $id = $this->getIntInput("id");
        (new QuestionModel())->update($id,["content"=>$this->getInput("content"),"type"=>$this->getInput("type","text"),"difficulty"=>$this->getInput("difficulty","medium"),"language"=>$this->getInput("language")?:null,"tags"=>$this->getInput("tags")]);
        $this->jsonResponse(["success"=>true]);
    }

    public function delete(): void {
        $this->requireRole(["interviewer","hr_admin"]);
        if (!$this->validateCsrf()) { $this->jsonResponse(["error"=>"Invalid token"],403); return; }
        (new QuestionModel())->delete($this->getIntInput("id"));
        $this->jsonResponse(["success"=>true]);
    }
}