/* 자동 생성물 — registry.json 스냅샷. 직접 수정 금지. */
window.COPIER_COMPONENT_REGISTRY = {
    "schema_version": 1,
    "library_id": "copier-ui-components",
    "description": "CopierRMS 공용 UI 컴포넌트 정본. 이 파일이 MVP 단계의 source of truth 이다. Phase 2에서 components/*.json 으로 분리하고 이 파일은 생성물이 된다.",
    "generated_at": "2026-07-16",
    "categories": [
        "action",
        "navigation",
        "input",
        "data-display",
        "feedback",
        "overlay",
        "layout",
        "utility"
    ],
    "components": [
        {
            "schema_version": 1,
            "id": "primary-button-set",
            "title": "버튼 세트",
            "category": "action",
            "summary": "primary / ghost / danger 3종 버튼. CSS 변수 기반, 빌드 불필요.",
            "tags": [
                "button",
                "action",
                "버튼"
            ],
            "status": "active",
            "aliases": [],
            "html": "<button class='btn btn-primary'>저장</button> <button class='btn btn-ghost'>취소</button> <button class='btn btn-danger'>삭제</button>",
            "css": ".btn{cursor:pointer;font:inherit;font-weight:650;border-radius:8px;padding:9px 15px;border:1px solid transparent} .btn+.btn{margin-left:6px} .btn-primary{background:var(--accent);color:#fff} .btn-ghost{background:var(--bg2);color:var(--text);border:1px solid var(--border)} .btn-danger{background:var(--bg2);color:var(--danger);border:1px solid color-mix(in srgb,var(--danger) 40%,var(--border))}",
            "js": "",
            "dependencies": [],
            "project_refs": [
                "stockio.php"
            ],
            "source": {
                "type": "project",
                "project_path": "assets/css/style.css",
                "url": null,
                "author": "CopierRMS",
                "license": "project-internal",
                "attribution": "copier 공통 버튼 스타일을 독립 예제로 정리",
                "adaptation_notes": "실제 클래스명은 프로젝트 style.css 기준으로 검증 필요"
            },
            "verified": false,
            "verification": {
                "verified_at": null,
                "verified_by": [],
                "checks": []
            },
            "usage_notes": [
                "주 동작 1개만 primary, 나머지는 ghost",
                "되돌릴 수 없는 삭제는 danger"
            ],
            "known_pitfalls": [],
            "related_components": [],
            "supersedes": null,
            "revision": 1,
            "created_at": "2026-07-16",
            "updated_at": "2026-07-16"
        },
        {
            "schema_version": 1,
            "id": "autocomplete-floating-panel",
            "title": "떠있는 자동완성 목록",
            "category": "input",
            "summary": "스크롤과 키보드 이동을 지원하는 바닐라 JS 자동완성. 페이지 스크롤 시 닫지 않고 재배치.",
            "tags": [
                "autocomplete",
                "keyboard",
                "popover",
                "자동완성"
            ],
            "status": "active",
            "aliases": [],
            "html": "<label style='font-size:12px;color:var(--text2);display:block;margin-bottom:5px'>거래처 검색</label><input id='ac' type='text' autocomplete='off' placeholder='업체명 입력…' style='width:100%;max-width:280px;padding:9px 11px;border:1px solid var(--border);border-radius:8px;background:var(--bg2);color:var(--text)'>",
            "css": ".ac-menu{position:absolute;z-index:900;max-height:200px;overflow-y:auto;overscroll-behavior:contain;background:var(--bg2);border:1px solid var(--border);border-radius:8px;box-shadow:0 10px 30px rgba(20,30,60,.15);padding:5px;min-width:200px} .ac-item{padding:8px 11px;border-radius:6px;cursor:pointer;font-size:13px;color:var(--text)} .ac-item.hi{background:var(--accent);color:#fff}",
            "js": "var DATA=['(주)가야','(주)건화','현대자동차','영주시청','부석사','풍기읍사무소','신영주교회'];var input=document.getElementById('ac');var menu=null,items=[],hi=-1;function pos(){if(!menu)return;var r=input.getBoundingClientRect();menu.style.left=(scrollX+r.left)+'px';menu.style.top=(scrollY+r.bottom+4)+'px'}function close(){if(menu){menu.remove();menu=null;items=[];hi=-1}}function paint(){if(!menu)return;[].forEach.call(menu.children,function(c,i){c.classList.toggle('hi',i===hi)});var cur=menu.children[hi];if(cur){var t=cur.offsetTop,b=t+cur.offsetHeight;if(t<menu.scrollTop)menu.scrollTop=t;else if(b>menu.scrollTop+menu.clientHeight)menu.scrollTop=b-menu.clientHeight}}function open(){close();var q=input.value.trim().toLowerCase();items=DATA.filter(function(n){return n.toLowerCase().indexOf(q)>=0});hi=items.length?0:-1;menu=document.createElement('div');menu.className='ac-menu';items.forEach(function(n,i){var d=document.createElement('div');d.className='ac-item'+(i===hi?' hi':'');d.textContent=n;d.addEventListener('mousedown',function(e){e.preventDefault();input.value=n;close()});d.addEventListener('mouseenter',function(){hi=i;paint()});menu.appendChild(d)});document.body.appendChild(menu);pos()}input.addEventListener('focus',open);input.addEventListener('input',open);input.addEventListener('blur',function(){setTimeout(close,120)});input.addEventListener('keydown',function(e){if(!menu||!items.length)return;if(e.key==='ArrowDown'){e.preventDefault();hi=Math.min(hi+1,items.length-1);paint()}else if(e.key==='ArrowUp'){e.preventDefault();hi=Math.max(hi-1,0);paint()}else if(e.key==='Enter'){e.preventDefault();if(hi>=0){input.value=items[hi];close()}}else if(e.key==='Escape'){close()}});addEventListener('scroll',function(e){if(!menu)return;if(e.target instanceof Node&&menu.contains(e.target))return;pos()},true);",
            "dependencies": [],
            "project_refs": [
                "stockio.php",
                "G:/내 드라이브/copier_메모리/00_공통/05_UI_공통패턴_함정노트.md"
            ],
            "source": {
                "type": "project",
                "project_path": "stockio.php",
                "url": null,
                "author": "CopierRMS",
                "license": "project-internal",
                "attribution": "stockio.php에서 검증된 자동완성 동작을 독립 예제로 정리",
                "adaptation_notes": "운영 데이터와 PHP 처리를 제거한 UI 전용 예제"
            },
            "verified": false,
            "verification": {
                "verified_at": null,
                "verified_by": [],
                "checks": []
            },
            "usage_notes": [
                "색상은 CSS 변수로 지정",
                "목록 내부 스크롤은 전역 scroll 처리에서 제외"
            ],
            "known_pitfalls": [
                "scrollIntoView는 페이지까지 움직일 수 있으므로 목록 scrollTop을 직접 조정한다."
            ],
            "related_components": [],
            "supersedes": null,
            "revision": 1,
            "created_at": "2026-07-16",
            "updated_at": "2026-07-16"
        },
        {
            "schema_version": 1,
            "id": "confirmation-dialog",
            "title": "확인 다이얼로그",
            "category": "overlay",
            "summary": "window.confirm 대체. 내장 <dialog> 기반 Promise 확인창, danger 강조.",
            "tags": [
                "dialog",
                "confirm",
                "overlay",
                "확인창"
            ],
            "status": "active",
            "aliases": [],
            "html": "<button id='cf' style='cursor:pointer;font:inherit;font-weight:650;border-radius:8px;padding:9px 15px;border:1px solid var(--border);background:var(--bg2);color:var(--danger)'>이력 취소</button>",
            "css": "dialog.cf{border:1px solid var(--border);border-radius:10px;background:var(--bg2);color:var(--text);padding:0;width:min(90vw,340px);text-align:center} dialog.cf::backdrop{background:rgba(10,14,22,.5)} .cf-b{padding:22px 20px 6px} .cf-i{width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;font-size:22px;font-weight:800;background:color-mix(in srgb,var(--danger) 15%,transparent);color:var(--danger)} .cf-b h3{margin:0 0 4px;font-size:15px} .cf-b p{margin:0;font-size:13px;color:var(--text2)} .cf-f{padding:16px 20px;display:flex;gap:8px;justify-content:center} .cf-f button{cursor:pointer;font:inherit;font-weight:650;border-radius:8px;padding:8px 16px;border:1px solid var(--border);background:var(--bg2);color:var(--text)} .cf-f .ok{background:var(--danger);color:#fff;border-color:var(--danger)}",
            "js": "function confirmModal(o){o=o||{};return new Promise(function(res){var d=document.createElement('dialog');d.className='cf';var b=document.createElement('div');b.className='cf-b';var i=document.createElement('div');i.className='cf-i';i.textContent='!';var h=document.createElement('h3');h.textContent=o.title||'확인';var p=document.createElement('p');p.textContent=o.message||'';b.appendChild(i);b.appendChild(h);b.appendChild(p);var f=document.createElement('div');f.className='cf-f';var c=document.createElement('button');c.textContent=o.cancelText||'취소';var k=document.createElement('button');k.className='ok';k.textContent=o.confirmText||'확인';c.onclick=function(){d.close('c')};k.onclick=function(){d.close('ok')};f.appendChild(c);f.appendChild(k);d.appendChild(b);d.appendChild(f);d.addEventListener('close',function(){res(d.returnValue==='ok');d.remove()});document.body.appendChild(d);d.showModal()})}document.getElementById('cf').addEventListener('click',function(){confirmModal({title:'이력을 취소할까요?',message:'재고 수량이 되돌려집니다.',confirmText:'취소 처리'})});",
            "dependencies": [],
            "project_refs": [
                "stockio.php"
            ],
            "source": {
                "type": "project",
                "project_path": "stockio.php",
                "url": null,
                "author": "CopierRMS",
                "license": "project-internal",
                "attribution": "SweetAlert2 패턴을 내장 <dialog>로 경량 구현",
                "adaptation_notes": "제목·본문은 textContent로 넣어 주입 방지"
            },
            "verified": false,
            "verification": {
                "verified_at": null,
                "verified_by": [],
                "checks": []
            },
            "usage_notes": [
                "const ok = await confirmModal({...}) 로 결과 사용",
                "미리보기에는 sandbox allow-modals 필요"
            ],
            "known_pitfalls": [
                "showModal()은 상위 프레임 권한(allow-modals)이 없으면 동작하지 않음"
            ],
            "related_components": [],
            "supersedes": null,
            "revision": 1,
            "created_at": "2026-07-16",
            "updated_at": "2026-07-16"
        },
        {
            "schema_version": 1,
            "id": "sidebar-accordion-menu",
            "title": "사이드바 하위메뉴",
            "category": "navigation",
            "summary": "큰 메뉴 클릭 시 하위메뉴가 아코디언으로 펼쳐지는 사이드 내비게이션.",
            "tags": [
                "sidebar",
                "accordion",
                "menu",
                "메뉴"
            ],
            "status": "active",
            "aliases": [],
            "html": "<nav class='side2' id='acc'><div><div class='smp open' data-t='m1'>재고 관리<span class='cr'>›</span></div><div class='sm open' id='m1'><a>토너 현황</a><a>소모품 재고</a><a>입출고</a></div></div><div><div class='smp' data-t='m2'>정산<span class='cr'>›</span></div><div class='sm' id='m2'><a>정산·수익</a><a>세금계산서</a></div></div></nav>",
            "css": ".side2{width:230px;max-width:100%;background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:8px} .smp{display:flex;align-items:center;padding:9px 10px;border-radius:7px;cursor:pointer;font-weight:600;font-size:13px;color:var(--text)} .smp:hover{background:var(--bg3)} .smp.open{color:var(--accent)} .cr{margin-left:auto;transition:transform .18s} .smp.open .cr{transform:rotate(90deg)} .sm{overflow:hidden;max-height:0;transition:max-height .22s} .sm.open{max-height:200px} .sm a{display:block;padding:7px 10px 7px 24px;border-radius:6px;color:var(--text2);font-size:12.5px;text-decoration:none;cursor:pointer} .sm a:hover{background:var(--bg3);color:var(--text)}",
            "js": "document.getElementById('acc').addEventListener('click',function(e){var p=e.target.closest('.smp');if(!p)return;var s=document.getElementById(p.dataset.t);p.classList.toggle('open');s.classList.toggle('open')});",
            "dependencies": [],
            "project_refs": [
                "knowledge_center.php"
            ],
            "source": {
                "type": "project",
                "project_path": null,
                "url": null,
                "author": "CopierRMS",
                "license": "project-internal",
                "attribution": "일반 관리자 사이드바 패턴",
                "adaptation_notes": "max-height 트랜지션으로 접힘/펼침"
            },
            "verified": false,
            "verification": {
                "verified_at": null,
                "verified_by": [],
                "checks": []
            },
            "usage_notes": [
                "항목 많은 관리자 화면에 적합"
            ],
            "known_pitfalls": [],
            "related_components": [],
            "supersedes": null,
            "revision": 1,
            "created_at": "2026-07-16",
            "updated_at": "2026-07-16"
        },
        {
            "schema_version": 1,
            "id": "inline-alert-banner",
            "title": "알림 배너",
            "category": "feedback",
            "summary": "정보/경고/오류/성공 인라인 배너. 의미색은 아이콘·테두리로 구분.",
            "tags": [
                "alert",
                "banner",
                "feedback",
                "알림"
            ],
            "status": "active",
            "aliases": [],
            "html": "<div class='alert warn'><span class='ico'>⚠</span><div><b>부족 재고 35건</b> — 소모품 발주가 필요합니다.</div></div>",
            "css": ".alert{display:flex;gap:10px;align-items:flex-start;padding:11px 13px;border-radius:8px;font-size:13px;color:var(--text);border:1px solid var(--border);background:var(--bg2)} .alert.warn{border-color:color-mix(in srgb,var(--warning) 38%,var(--border));background:color-mix(in srgb,var(--warning) 9%,var(--bg2))} .alert.warn .ico{color:var(--warning)}",
            "js": "",
            "dependencies": [],
            "project_refs": [],
            "source": {
                "type": "project",
                "project_path": null,
                "url": null,
                "author": "CopierRMS",
                "license": "project-internal",
                "attribution": "인라인 피드백 배너 패턴",
                "adaptation_notes": "class(info/warn/err/ok) 교체로 의미색 전환"
            },
            "verified": false,
            "verification": {
                "verified_at": null,
                "verified_by": [],
                "checks": []
            },
            "usage_notes": [
                "오류는 무엇이 왜, 어떻게를 함께 표기"
            ],
            "known_pitfalls": [],
            "related_components": [],
            "supersedes": null,
            "revision": 1,
            "created_at": "2026-07-16",
            "updated_at": "2026-07-16"
        },
        {
            "schema_version": 1,
            "id": "data-table-basic",
            "title": "데이터 테이블",
            "category": "data-display",
            "summary": "상태칩과 우측정렬 숫자를 갖춘 기본 이력 테이블. 가로 스크롤 대응.",
            "tags": [
                "table",
                "data-display",
                "테이블"
            ],
            "status": "active",
            "aliases": [],
            "html": "<div class='tw'><table class='tbl'><thead><tr><th>날짜</th><th>유형</th><th>품명</th><th class='n'>수량</th><th>상태</th></tr></thead><tbody><tr><td>26-07-15</td><td><span class='bg out'>출고</span></td><td>DWA4</td><td class='n'>-2</td><td><span class='bg ok'>정상</span></td></tr><tr><td>26-07-09</td><td><span class='bg in'>입고</span></td><td>D450검토너</td><td class='n'>+10</td><td><span class='bg ok'>정상</span></td></tr></tbody></table></div>",
            "css": ".tw{overflow-x:auto;border:1px solid var(--border);border-radius:8px} table.tbl{width:100%;border-collapse:collapse;font-size:13px;min-width:360px} table.tbl th{text-align:left;background:var(--bg3);color:var(--text2);font-size:11px;font-weight:700;padding:9px 12px;border-bottom:1px solid var(--border)} table.tbl td{padding:9px 12px;border-bottom:1px solid var(--border);color:var(--text)} table.tbl tbody tr:last-child td{border-bottom:none} table.tbl .n{text-align:right;font-variant-numeric:tabular-nums} .bg{font-size:11px;font-weight:650;padding:2px 8px;border-radius:100px} .bg.in{color:var(--success);background:color-mix(in srgb,var(--success) 15%,transparent)} .bg.out{color:var(--danger);background:color-mix(in srgb,var(--danger) 15%,transparent)} .bg.ok{color:var(--success);background:color-mix(in srgb,var(--success) 12%,transparent)}",
            "js": "",
            "dependencies": [],
            "project_refs": [
                "stockio.php"
            ],
            "source": {
                "type": "project",
                "project_path": "stockio.php",
                "url": null,
                "author": "CopierRMS",
                "license": "project-internal",
                "attribution": "입출고 이력 테이블을 독립 예제로 정리",
                "adaptation_notes": "PHP foreach 자리만 정적 행으로 대체"
            },
            "verified": false,
            "verification": {
                "verified_at": null,
                "verified_by": [],
                "checks": []
            },
            "usage_notes": [
                "숫자열은 tabular-nums로 자릿수 정렬",
                "넓은 표는 .tw로 가로 스크롤"
            ],
            "known_pitfalls": [],
            "related_components": [],
            "supersedes": null,
            "revision": 1,
            "created_at": "2026-07-16",
            "updated_at": "2026-07-16"
        },
        {
            "schema_version": 1,
            "id": "list-menu",
            "title": "목록형 메뉴 (섹션·하위메뉴)",
            "category": "navigation",
            "summary": "섹션 제목·활성 항목·접히는 하위메뉴를 갖춘 목록 메뉴. daisyUI menu를 토큰 CSS로 이식(네이티브 <details>, JS 불필요).",
            "tags": [
                "menu",
                "navigation",
                "submenu",
                "sidebar",
                "메뉴"
            ],
            "status": "active",
            "aliases": [],
            "html": "<ul class='menu'><li class='menu-title'>재고</li><li><a class='active'><svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><path d='M12 3s6 6 6 10a6 6 0 0 1-12 0c0-4 6-10 6-10z'/></svg>토너 현황</a></li><li><a><svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><path d='M21 8l-9-5-9 5 9 5 9-5z'/><path d='M3 8v8l9 5 9-5V8'/></svg>소모품 재고</a></li><li><details open><summary><svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><path d='M7 7L3 11l4 4'/><path d='M3 11h12'/><path d='M17 17l4-4-4-4'/><path d='M21 13H10'/></svg>입출고</summary><ul><li><a>출고</a></li><li><a>입고</a></li><li><a>반품</a></li></ul></details></li><li class='menu-title'>정산</li><li><a>정산·수익</a></li><li><a>세금계산서</a></li></ul>",
            "css": ".menu{background:var(--bg2);border:1px solid var(--border);border-radius:12px;padding:8px;width:240px;max-width:100%;font-size:13px} .menu ul{list-style:none;margin:0;padding:0} .menu li{list-style:none} .menu-title{padding:8px 12px 4px;font-size:11px;font-weight:700;letter-spacing:.04em;color:var(--text3)} .menu a,.menu summary{display:flex;align-items:center;gap:9px;padding:8px 12px;border-radius:8px;color:var(--text2);text-decoration:none;cursor:pointer} .menu a:hover,.menu summary:hover{background:var(--bg3);color:var(--text)} .menu a.active{background:var(--accent);color:#fff} .menu svg{width:16px;height:16px;flex-shrink:0} .menu details>summary{list-style:none} .menu details>summary::-webkit-details-marker{display:none} .menu details>summary::after{content:'';margin-left:auto;width:7px;height:7px;border-right:2px solid currentColor;border-bottom:2px solid currentColor;transform:rotate(-45deg);transition:transform .18s;opacity:.55} .menu details[open]>summary::after{transform:rotate(45deg)} .menu details>ul{margin:2px 0 2px 14px;padding-left:12px;border-left:1px solid var(--border)} .menu details>ul a{padding:7px 10px;font-size:12.5px}",
            "js": "",
            "dependencies": [],
            "project_refs": [],
            "source": {
                "type": "external",
                "project_path": null,
                "url": "https://daisyui.com/components/menu/",
                "author": "daisyUI",
                "license": "MIT (패턴 참고, CSS는 토큰으로 자체 작성)",
                "attribution": "daisyUI menu 디자인을 CopierRMS CSS 변수로 이식",
                "adaptation_notes": "Tailwind/daisyUI 클래스 제거, 하위메뉴는 네이티브 <details>로 접힘 처리(JS 불필요)"
            },
            "verified": false,
            "verification": {
                "verified_at": null,
                "verified_by": [],
                "checks": []
            },
            "usage_notes": [
                "섹션은 .menu-title, 활성 항목은 a.active",
                "하위메뉴는 <details>로 감싸면 클릭 시 접힘/펼침"
            ],
            "known_pitfalls": [
                "JS 없이 동작하므로 <details> 지원 브라우저 필요(모던 전부 지원)"
            ],
            "related_components": [
                "sidebar-accordion-menu"
            ],
            "supersedes": null,
            "revision": 1,
            "created_at": "2026-07-16",
            "updated_at": "2026-07-16"
        },
        {
            "schema_version": 1,
            "id": "tooltip",
            "title": "툴팁",
            "category": "overlay",
            "summary": "hover와 focus 모두에서 열리는 필드 도움말 툴팁. 화면 밖 보정.",
            "tags": [
                "tooltip",
                "popover",
                "help",
                "툴팁"
            ],
            "status": "active",
            "aliases": [],
            "html": "<span id='tip' tabindex='0' class='tip-anchor'>폐토너 잔량 <span class='tip-i'>i</span></span>",
            "css": ".tip-anchor{display:inline-flex;align-items:center;gap:6px;cursor:help;color:var(--text2);font-size:13px} .tip-i{display:inline-flex;width:18px;height:18px;align-items:center;justify-content:center;border-radius:50%;background:var(--accent-soft);color:var(--accent);font-size:11px;font-weight:700;font-style:normal} .tip-pop{position:absolute;z-index:900;max-width:220px;background:var(--text);color:var(--bg2);font-size:12px;padding:8px 11px;border-radius:6px;box-shadow:0 10px 30px rgba(20,30,60,.18)}",
            "js": "var a=document.getElementById('tip');var p=null;function show(){if(p)return;p=document.createElement('div');p.className='tip-pop';p.textContent='남은 폐토너 용량(%)입니다. 사용량 = 100 - 이 값.';document.body.appendChild(p);var r=a.getBoundingClientRect(),t=p.getBoundingClientRect();var l=scrollX+r.left,m=scrollX+document.documentElement.clientWidth-t.width-8;p.style.left=Math.min(l,m)+'px';p.style.top=(scrollY+r.top-t.height-8)+'px'}function hide(){if(p){p.remove();p=null}}a.addEventListener('mouseenter',show);a.addEventListener('mouseleave',hide);a.addEventListener('focus',show);a.addEventListener('blur',hide);",
            "dependencies": [],
            "project_refs": [],
            "source": {
                "type": "project",
                "project_path": null,
                "url": null,
                "author": "CopierRMS",
                "license": "project-internal",
                "attribution": "UI 컴포넌트 템플릿(쇼케이스)에서 이관",
                "adaptation_notes": "자립형, 토큰 CSS"
            },
            "verified": false,
            "verification": {
                "verified_at": null,
                "verified_by": [],
                "checks": []
            },
            "usage_notes": [
                "hover와 focus 둘 다에서 열어 키보드 접근성 확보"
            ],
            "known_pitfalls": [],
            "related_components": [],
            "supersedes": null,
            "revision": 1,
            "created_at": "2026-07-16",
            "updated_at": "2026-07-16"
        },
        {
            "schema_version": 1,
            "id": "toast-notification",
            "title": "토스트 알림",
            "category": "feedback",
            "summary": "우측 하단에 쌓였다 사라지는 성공/오류 알림.",
            "tags": [
                "toast",
                "notification",
                "feedback",
                "알림"
            ],
            "status": "active",
            "aliases": [],
            "html": "<button id='tbtn' class='tbtn'>저장</button><div id='thost' class='toast-host'></div>",
            "css": ".tbtn{cursor:pointer;font:inherit;font-weight:650;border-radius:8px;padding:9px 15px;border:none;background:var(--accent);color:#fff} .toast-host{position:fixed;right:16px;bottom:16px;display:flex;flex-direction:column;gap:8px;z-index:100} .toast{background:var(--bg2);color:var(--text);border:1px solid var(--border);border-left:3px solid var(--success);border-radius:8px;box-shadow:0 10px 30px rgba(20,30,60,.2);padding:10px 13px;font-size:13px;font-weight:600} .toast.err{border-left-color:var(--danger)}",
            "js": "function showToast(msg,ok){var h=document.getElementById('thost');var t=document.createElement('div');t.className='toast'+(ok?'':' err');t.textContent=msg;h.appendChild(t);setTimeout(function(){t.style.opacity='0';t.style.transition='opacity .3s';setTimeout(function(){t.remove()},300)},2200)}document.getElementById('tbtn').addEventListener('click',function(){showToast('저장되었습니다.',true)});",
            "dependencies": [],
            "project_refs": [],
            "source": {
                "type": "project",
                "project_path": null,
                "url": null,
                "author": "CopierRMS",
                "license": "project-internal",
                "attribution": "UI 컴포넌트 템플릿(쇼케이스)에서 이관",
                "adaptation_notes": "자립형, 토큰 CSS"
            },
            "verified": false,
            "verification": {
                "verified_at": null,
                "verified_by": [],
                "checks": []
            },
            "usage_notes": [
                "성공=녹색, 오류=적색 좌측 바",
                "메시지는 결과를 사실로"
            ],
            "known_pitfalls": [],
            "related_components": [],
            "supersedes": null,
            "revision": 1,
            "created_at": "2026-07-16",
            "updated_at": "2026-07-16"
        },
        {
            "schema_version": 1,
            "id": "slide-over-drawer",
            "title": "슬라이드 패널 (Drawer)",
            "category": "overlay",
            "summary": "오른쪽에서 슬라이드되는 보조 패널. 스크림·Esc로 닫힘.",
            "tags": [
                "drawer",
                "offcanvas",
                "panel",
                "패널"
            ],
            "status": "active",
            "aliases": [],
            "html": "<button id='odr' class='dbtn'>패널 열기</button><div id='scr' class='drawer-scrim'></div><aside id='drw' class='drawer'><div class='drawer-head'>소모품 현재고<button id='cdr' class='dx'>X</button></div><div class='drawer-body'>부족 품목을 이 패널에서 확인합니다.</div></aside>",
            "css": ".dbtn{cursor:pointer;font:inherit;font-weight:650;border-radius:8px;padding:9px 15px;border:1px solid var(--border);background:var(--bg2);color:var(--text)} .drawer-scrim{position:fixed;inset:0;background:rgba(10,14,22,.5);opacity:0;pointer-events:none;transition:opacity .25s;z-index:990} .drawer-scrim.open{opacity:1;pointer-events:auto} .drawer{position:fixed;top:0;right:0;height:100%;width:min(85vw,300px);background:var(--bg2);border-left:1px solid var(--border);transform:translateX(100%);transition:transform .25s;z-index:991;display:flex;flex-direction:column} .drawer.open{transform:translateX(0)} .drawer-head{padding:14px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;font-weight:700} .dx{margin-left:auto;background:none;border:none;color:var(--text3);cursor:pointer;font-size:14px} .drawer-body{padding:16px;font-size:13px;color:var(--text2)}",
            "js": "var d=document.getElementById('drw'),s=document.getElementById('scr');function o(){d.classList.add('open');s.classList.add('open')}function c(){d.classList.remove('open');s.classList.remove('open')}document.getElementById('odr').addEventListener('click',o);document.getElementById('cdr').addEventListener('click',c);s.addEventListener('click',c);addEventListener('keydown',function(e){if(e.key==='Escape')c()});",
            "dependencies": [],
            "project_refs": [],
            "source": {
                "type": "project",
                "project_path": null,
                "url": null,
                "author": "CopierRMS",
                "license": "project-internal",
                "attribution": "UI 컴포넌트 템플릿(쇼케이스)에서 이관",
                "adaptation_notes": "자립형, 토큰 CSS"
            },
            "verified": false,
            "verification": {
                "verified_at": null,
                "verified_by": [],
                "checks": []
            },
            "usage_notes": [
                "넓은 보조 화면(재고·설정 패널)에 적합"
            ],
            "known_pitfalls": [],
            "related_components": [
                "bottom-sheet"
            ],
            "supersedes": null,
            "revision": 1,
            "created_at": "2026-07-16",
            "updated_at": "2026-07-16"
        },
        {
            "schema_version": 1,
            "id": "bottom-sheet",
            "title": "하단 팝업 (액션시트)",
            "category": "overlay",
            "summary": "화면 아래에서 올라오는 모바일 친화 동작 메뉴.",
            "tags": [
                "sheet",
                "actionsheet",
                "mobile",
                "팝업"
            ],
            "status": "active",
            "aliases": [],
            "html": "<button id='osh' class='dbtn'>동작 메뉴</button><div id='ssc' class='sheet-scrim'></div><div id='sht' class='sheet'><div class='sheet-grip'></div><div class='sheet-item'>반품 처리</div><div class='sheet-item'>단가 수정</div><div class='sheet-item danger'>이력 취소</div></div>",
            "css": ".dbtn{cursor:pointer;font:inherit;font-weight:650;border-radius:8px;padding:9px 15px;border:1px solid var(--border);background:var(--bg2);color:var(--text)} .sheet-scrim{position:fixed;inset:0;background:rgba(10,14,22,.5);opacity:0;pointer-events:none;transition:opacity .22s;z-index:995} .sheet-scrim.open{opacity:1;pointer-events:auto} .sheet{position:fixed;left:0;right:0;bottom:0;max-width:480px;margin:0 auto;background:var(--bg2);border-top:1px solid var(--border);border-radius:16px 16px 0 0;transform:translateY(100%);transition:transform .26s cubic-bezier(.32,.72,0,1);z-index:996;padding:8px 12px 16px} .sheet.open{transform:translateY(0)} .sheet-grip{width:38px;height:4px;border-radius:100px;background:var(--border);margin:6px auto 8px} .sheet-item{padding:13px 12px;border-radius:9px;font-size:14px;color:var(--text);cursor:pointer} .sheet-item:hover{background:var(--bg3)} .sheet-item.danger{color:var(--danger)}",
            "js": "var s=document.getElementById('sht'),sc=document.getElementById('ssc');function c(){s.classList.remove('open');sc.classList.remove('open')}document.getElementById('osh').addEventListener('click',function(){s.classList.add('open');sc.classList.add('open')});sc.addEventListener('click',c);addEventListener('keydown',function(e){if(e.key==='Escape')c()});s.querySelectorAll('.sheet-item').forEach(function(it){it.addEventListener('click',c)});",
            "dependencies": [],
            "project_refs": [],
            "source": {
                "type": "project",
                "project_path": null,
                "url": null,
                "author": "CopierRMS",
                "license": "project-internal",
                "attribution": "UI 컴포넌트 템플릿(쇼케이스)에서 이관",
                "adaptation_notes": "자립형, 토큰 CSS"
            },
            "verified": false,
            "verification": {
                "verified_at": null,
                "verified_by": [],
                "checks": []
            },
            "usage_notes": [
                "모바일에서 한 행의 여러 동작에 적합"
            ],
            "known_pitfalls": [],
            "related_components": [
                "slide-over-drawer"
            ],
            "supersedes": null,
            "revision": 1,
            "created_at": "2026-07-16",
            "updated_at": "2026-07-16"
        },
        {
            "schema_version": 1,
            "id": "form-controls",
            "title": "폼 컨트롤",
            "category": "input",
            "summary": "입력·셀렉트·메모·체크박스·토글 스위치를 .inp 한 스타일로 통일.",
            "tags": [
                "form",
                "input",
                "select",
                "toggle",
                "폼"
            ],
            "status": "active",
            "aliases": [],
            "html": "<div class='form-grid'><div><label class='fl'>거래처</label><input class='inp' placeholder='업체 검색...'></div><div><label class='fl'>유형</label><select class='inp'><option>출고</option><option>입고</option><option>반품</option></select></div><div class='full'><label class='fl'>메모</label><textarea class='inp'></textarea></div><div class='full frow'><label class='ck'><input type='checkbox' checked> 임대 품목</label><label class='sw'><input type='checkbox' checked><span class='tk'></span> 알림 받기</label></div></div>",
            "css": ".form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;max-width:420px} .form-grid .full{grid-column:1/-1} .fl{font-size:11px;color:var(--text2);font-weight:600;display:block;margin-bottom:5px} .inp{width:100%;background:var(--bg2);color:var(--text);border:1px solid var(--border);border-radius:8px;padding:8px 10px;font:inherit;font-size:13px;outline:none} .inp:focus-visible{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-soft)} textarea.inp{min-height:60px;resize:vertical} .frow{display:flex;gap:16px;align-items:center;flex-wrap:wrap} .ck{display:inline-flex;align-items:center;gap:8px;font-size:13px;color:var(--text)} .ck input{accent-color:var(--accent)} .sw{position:relative;display:inline-flex;align-items:center;gap:9px;cursor:pointer;font-size:13px} .sw input{position:absolute;opacity:0} .sw .tk{width:38px;height:22px;border-radius:100px;background:var(--bg3);border:1px solid var(--border);position:relative;transition:background .15s} .sw .tk::after{content:'';position:absolute;top:2px;left:2px;width:16px;height:16px;border-radius:50%;background:#fff;transition:transform .15s} .sw input:checked+.tk{background:var(--accent);border-color:var(--accent)} .sw input:checked+.tk::after{transform:translateX(16px)}",
            "js": "",
            "dependencies": [],
            "project_refs": [],
            "source": {
                "type": "project",
                "project_path": null,
                "url": null,
                "author": "CopierRMS",
                "license": "project-internal",
                "attribution": "UI 컴포넌트 템플릿(쇼케이스)에서 이관",
                "adaptation_notes": "자립형, 토큰 CSS"
            },
            "verified": false,
            "verification": {
                "verified_at": null,
                "verified_by": [],
                "checks": []
            },
            "usage_notes": [
                "모든 컨트롤은 .inp 한 클래스로 통일",
                "0 허용 숫자칸은 value||기본값 금지"
            ],
            "known_pitfalls": [],
            "related_components": [],
            "supersedes": null,
            "revision": 1,
            "created_at": "2026-07-16",
            "updated_at": "2026-07-16"
        },
        {
            "schema_version": 1,
            "id": "tabs",
            "title": "탭",
            "category": "navigation",
            "summary": "패널 전환 탭. 활성 항목 밑줄 강조.",
            "tags": [
                "tabs",
                "navigation",
                "view",
                "탭"
            ],
            "status": "active",
            "aliases": [],
            "html": "<div class='tabs'><button class='tab active' data-p='pa'>전체</button><button class='tab' data-p='pi'>입고</button><button class='tab' data-p='po'>출고</button></div><div class='tabpanel' id='pa'>전체 이력 36건을 시간순으로 표시합니다.</div><div class='tabpanel' id='pi' hidden>입고 이력만 표시합니다.</div><div class='tabpanel' id='po' hidden>출고 이력만 표시합니다.</div>",
            "css": ".tabs{display:flex;gap:2px;border-bottom:1px solid var(--border)} .tab{background:none;border:none;cursor:pointer;font:inherit;font-size:13px;font-weight:650;color:var(--text2);padding:9px 14px;border-bottom:2px solid transparent;margin-bottom:-1px} .tab:hover{color:var(--text)} .tab.active{color:var(--accent);border-bottom-color:var(--accent)} .tabpanel{padding:14px 2px;font-size:13px;color:var(--text2)} .tabpanel[hidden]{display:none}",
            "js": "var t=document.querySelector('.tabs');t.addEventListener('click',function(e){var b=e.target.closest('.tab');if(!b)return;t.querySelectorAll('.tab').forEach(function(x){x.classList.remove('active')});b.classList.add('active');document.querySelectorAll('.tabpanel').forEach(function(p){p.hidden=(p.id!==b.dataset.p)})});",
            "dependencies": [],
            "project_refs": [],
            "source": {
                "type": "project",
                "project_path": null,
                "url": null,
                "author": "CopierRMS",
                "license": "project-internal",
                "attribution": "UI 컴포넌트 템플릿(쇼케이스)에서 이관",
                "adaptation_notes": "자립형, 토큰 CSS"
            },
            "verified": false,
            "verification": {
                "verified_at": null,
                "verified_by": [],
                "checks": []
            },
            "usage_notes": [
                "항목 적은 뷰 전환에 적합"
            ],
            "known_pitfalls": [],
            "related_components": [],
            "supersedes": null,
            "revision": 1,
            "created_at": "2026-07-16",
            "updated_at": "2026-07-16"
        },
        {
            "schema_version": 1,
            "id": "top-navbar",
            "title": "상단 가로 메뉴 (드롭다운)",
            "category": "navigation",
            "summary": "브랜드+가로 메뉴, 항목 클릭 시 드롭다운. 바깥 클릭 닫힘.",
            "tags": [
                "navbar",
                "topnav",
                "dropdown",
                "메뉴"
            ],
            "status": "active",
            "aliases": [],
            "html": "<div class='topnav' id='tn'><span class='brand2'>신도물산</span><a class='tnl active'>대시보드</a><a class='tnl'>장비관리</a><div class='tni'><button class='tnl' data-d>재고 v</button><div class='tnm'><a>토너 현황</a><a>소모품 재고</a><a>입출고</a></div></div><a class='tnl'>거래처</a></div>",
            "css": ".topnav{display:flex;align-items:center;gap:2px;background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:6px 8px;flex-wrap:wrap} .brand2{font-weight:800;font-size:14px;padding:6px 10px;margin-right:6px} .tni{position:relative} .tnl{display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border-radius:7px;color:var(--text2);font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;background:none;border:none;font-family:inherit} .tnl:hover,.tni.open .tnl{background:var(--bg3);color:var(--text)} .tnl.active{color:var(--accent)} .tnm{position:absolute;top:calc(100% + 6px);left:0;min-width:170px;background:var(--bg2);border:1px solid var(--border);border-radius:8px;box-shadow:0 10px 30px rgba(20,30,60,.18);padding:5px;display:none;z-index:20} .tni.open .tnm{display:block} .tnm a{display:block;padding:8px 11px;border-radius:6px;font-size:13px;color:var(--text2);text-decoration:none;cursor:pointer} .tnm a:hover{background:var(--bg3);color:var(--text)}",
            "js": "var tn=document.getElementById('tn');tn.addEventListener('click',function(e){var b=e.target.closest('[data-d]');var i=b?b.closest('.tni'):null;tn.querySelectorAll('.tni.open').forEach(function(x){if(x!==i)x.classList.remove('open')});if(i)i.classList.toggle('open');var lk=e.target.closest('.tnl');if(lk&&!b){tn.querySelectorAll('.tnl').forEach(function(l){l.classList.remove('active')});lk.classList.add('active')}});document.addEventListener('click',function(e){if(!e.target.closest('#tn'))tn.querySelectorAll('.tni.open').forEach(function(x){x.classList.remove('open')})});",
            "dependencies": [],
            "project_refs": [],
            "source": {
                "type": "project",
                "project_path": null,
                "url": null,
                "author": "CopierRMS",
                "license": "project-internal",
                "attribution": "UI 컴포넌트 템플릿(쇼케이스)에서 이관",
                "adaptation_notes": "자립형, 토큰 CSS"
            },
            "verified": false,
            "verification": {
                "verified_at": null,
                "verified_by": [],
                "checks": []
            },
            "usage_notes": [
                "항목 적은 넓은 화면·대시보드형에 적합"
            ],
            "known_pitfalls": [],
            "related_components": [
                "list-menu",
                "sidebar-accordion-menu"
            ],
            "supersedes": null,
            "revision": 1,
            "created_at": "2026-07-16",
            "updated_at": "2026-07-16"
        },
        {
            "schema_version": 1,
            "id": "pagination",
            "title": "페이지네이션",
            "category": "navigation",
            "summary": "목록 하단 페이지 이동. 현재 페이지 강조.",
            "tags": [
                "pagination",
                "pager",
                "navigation",
                "페이지"
            ],
            "status": "active",
            "aliases": [],
            "html": "<div class='pager' id='pg'><button aria-label='이전' disabled>&lsaquo;</button><button class='active'>1</button><button>2</button><button>3</button><button>...</button><button>9</button><button aria-label='다음'>&rsaquo;</button></div>",
            "css": ".pager{display:flex;gap:5px;align-items:center} .pager button{cursor:pointer;min-width:32px;height:32px;padding:0 8px;border:1px solid var(--border);background:var(--bg2);color:var(--text2);border-radius:7px;font:inherit;font-size:12.5px} .pager button:hover:not(:disabled):not(.active){border-color:var(--accent);color:var(--accent)} .pager button.active{background:var(--accent);color:#fff;border-color:var(--accent)} .pager button:disabled{opacity:.4;cursor:not-allowed}",
            "js": "var p=document.getElementById('pg');p.addEventListener('click',function(e){var b=e.target.closest('button');if(!b||b.disabled)return;if(!/^\\d+$/.test(b.textContent.trim()))return;p.querySelectorAll('button').forEach(function(x){x.classList.remove('active')});b.classList.add('active')});",
            "dependencies": [],
            "project_refs": [],
            "source": {
                "type": "project",
                "project_path": null,
                "url": null,
                "author": "CopierRMS",
                "license": "project-internal",
                "attribution": "UI 컴포넌트 템플릿(쇼케이스)에서 이관",
                "adaptation_notes": "자립형, 토큰 CSS"
            },
            "verified": false,
            "verification": {
                "verified_at": null,
                "verified_by": [],
                "checks": []
            },
            "usage_notes": [
                "숫자 버튼만 활성 전환, 숫자는 tabular-nums 권장"
            ],
            "known_pitfalls": [],
            "related_components": [
                "data-table-basic"
            ],
            "supersedes": null,
            "revision": 1,
            "created_at": "2026-07-16",
            "updated_at": "2026-07-16"
        },
        {
            "schema_version": 1,
            "id": "empty-state",
            "title": "빈 상태",
            "category": "feedback",
            "summary": "데이터 없을 때 안내 + 다음 행동 버튼.",
            "tags": [
                "empty",
                "placeholder",
                "feedback",
                "빈상태"
            ],
            "status": "active",
            "aliases": [],
            "html": "<div class='empty'><div class='empty-ico'><svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='1.6'><path d='M21 8l-9-5-9 5 9 5 9-5z'/><path d='M3 8v8l9 5 9-5V8'/></svg></div><h5>표시할 이력이 없습니다</h5><p>선택한 조건에 맞는 내역이 없어요.</p><button class='ebtn'>필터 초기화</button></div>",
            "css": ".empty{text-align:center;padding:30px 16px;border:1px dashed var(--border);border-radius:10px;max-width:360px} .empty-ico{width:44px;height:44px;margin:0 auto 10px;color:var(--text3)} .empty-ico svg{width:44px;height:44px} .empty h5{margin:0 0 4px;font-size:14px;font-weight:700;color:var(--text2)} .empty p{margin:0 0 14px;font-size:12.5px;color:var(--text3)} .ebtn{cursor:pointer;font:inherit;font-size:12px;font-weight:650;border:none;border-radius:8px;padding:7px 13px;background:var(--accent);color:#fff}",
            "js": "",
            "dependencies": [],
            "project_refs": [],
            "source": {
                "type": "project",
                "project_path": null,
                "url": null,
                "author": "CopierRMS",
                "license": "project-internal",
                "attribution": "UI 컴포넌트 템플릿(쇼케이스)에서 이관",
                "adaptation_notes": "자립형, 토큰 CSS"
            },
            "verified": false,
            "verification": {
                "verified_at": null,
                "verified_by": [],
                "checks": []
            },
            "usage_notes": [
                "빈 상태엔 다음 행동 버튼 하나 제시"
            ],
            "known_pitfalls": [],
            "related_components": [],
            "supersedes": null,
            "revision": 1,
            "created_at": "2026-07-16",
            "updated_at": "2026-07-16"
        },
        {
            "schema_version": 1,
            "id": "app-shell",
            "title": "앱 셸 (사이드바+상단바)",
            "category": "layout",
            "summary": "사이드바 내비 + 상단바 + 콘텐츠 레이아웃. 항목 클릭 시 제목 전환.",
            "tags": [
                "layout",
                "shell",
                "sidebar",
                "topbar",
                "레이아웃"
            ],
            "status": "active",
            "aliases": [],
            "html": "<div class='shell'><nav class='side'><div class='sbrand'>신도물산</div><a class='ni active'>대시보드</a><a class='ni'>장비관리</a><a class='ni'>거래처</a><a class='ni'>입출고</a></nav><div class='sbody'><div class='stop'>대시보드</div><div class='scont'><div class='kpis'><div class='kpi'><div class='k'>오늘 입고</div><div class='v'>+8</div></div><div class='kpi'><div class='k'>부족 재고</div><div class='v'>35</div></div></div></div></div></div>",
            "css": ".shell{display:flex;height:300px;border:1px solid var(--border);border-radius:10px;overflow:hidden} .side{width:150px;background:var(--bg2);border-right:1px solid var(--border);padding:8px;display:flex;flex-direction:column;gap:2px} .sbrand{font-weight:800;text-align:center;padding:8px 0;border-bottom:1px solid var(--border);margin-bottom:6px} .ni{padding:8px 10px;border-radius:7px;color:var(--text2);font-size:13px;cursor:pointer;text-decoration:none} .ni:hover{background:var(--bg3);color:var(--text)} .ni.active{background:var(--accent-soft);color:var(--accent);font-weight:700} .sbody{flex:1;display:flex;flex-direction:column;min-width:0} .stop{height:46px;border-bottom:1px solid var(--border);background:var(--bg2);display:flex;align-items:center;padding:0 14px;font-weight:700} .scont{flex:1;padding:14px;overflow:auto} .kpis{display:grid;grid-template-columns:1fr 1fr;gap:10px} .kpi{background:var(--bg2);border:1px solid var(--border);border-radius:8px;padding:12px} .kpi .k{font-size:11px;color:var(--text2)} .kpi .v{font-size:20px;font-weight:750;margin-top:4px}",
            "js": "var s=document.querySelector('.side');s.addEventListener('click',function(e){var a=e.target.closest('.ni');if(!a)return;s.querySelectorAll('.ni').forEach(function(x){x.classList.remove('active')});a.classList.add('active');document.querySelector('.stop').textContent=a.textContent});",
            "dependencies": [],
            "project_refs": [],
            "source": {
                "type": "project",
                "project_path": null,
                "url": null,
                "author": "CopierRMS",
                "license": "project-internal",
                "attribution": "UI 컴포넌트 템플릿(쇼케이스)에서 이관",
                "adaptation_notes": "자립형, 토큰 CSS, 실제 copier 메뉴 구조 반영"
            },
            "verified": false,
            "verification": {
                "verified_at": null,
                "verified_by": [],
                "checks": []
            },
            "usage_notes": [
                "항목 많은 관리자 화면 기본 레이아웃"
            ],
            "known_pitfalls": [],
            "related_components": [
                "sidebar-accordion-menu"
            ],
            "supersedes": null,
            "revision": 1,
            "created_at": "2026-07-16",
            "updated_at": "2026-07-16"
        }
    ]
};
