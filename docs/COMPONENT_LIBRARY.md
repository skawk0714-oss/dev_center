# 공유 UI 컴포넌트 라이브러리 (CopierRMS)

사람과 AI(Claude·Codex)가 함께 채우는 **재사용 UI 컴포넌트 지식베이스**. 각 컴포넌트는 복사해 바로 쓸 수 있는 HTML/CSS/JS 단위이며, 이 시스템의 CSS 변수(토큰) 위에서 동작한다.

> 이 문서는 Phase 1 MVP 기준이다. 규모가 커지면 Phase 2에서 컴포넌트별 파일로 분리한다(아래 로드맵).

---

## 1. 구성 파일

| 경로 | 역할 |
|---|---|
| `data/component_library/registry.json` | **정본(source of truth)**. 현재 MVP에서 유일한 원본. |
| `component_playground/registry.bundle.js` | 생성물. `registry.json`의 스냅샷을 `window.COPIER_COMPONENT_REGISTRY`로 노출(오프라인 `file://` fallback). |
| `component_playground/index.html` | 뷰어/에디터. 정본을 불러와 카드로 보여주고, 초안 작성·미리보기·기여 파일 내보내기를 제공. |
| `docs/COMPONENT_LIBRARY.md` | 이 문서(스키마·규칙·기여 흐름). |

**접속:** `http://localhost/dev_center/component_playground/`
오프라인일 때는 `component_playground/index.html`을 `file://`로 열면 번들 스냅샷으로 동작한다.

> 🔒 dev_center의 `data/`는 `.htaccess`(`Require all denied`)로 **웹 접근이 차단**되어 있다(구글드라이브 자격증명 등 민감파일 보호). 따라서 뷰어는 `registry.json`을 HTTP로 직접 fetch하지 않고, 웹 접근 가능한 `component_playground/registry.bundle.js` 스냅샷을 정본 전달 수단으로 사용한다. **`registry.json`을 편집하면 반드시 번들을 재생성**해야 뷰어에 반영된다(§5 명령 참고).

> ⚠️ `registry.json`이 MVP의 임시 정본이다. **Phase 2에서 `components/*.json`으로 분리**하고 `registry.json`·`registry.bundle.js`는 빌드 스크립트가 자동 생성하는 파생물로 바뀐다.

---

## 2. 정본/초안/캐시 관계

- **정본(공유)**: `registry.json`. Git으로 버전관리. 읽기 전용으로 표시.
- **초안(draft)**: 브라우저 `localStorage`에만 저장. 편집 가능. 정본이 아니다.
- **캐시/번들**: `registry.bundle.js`(오프라인)와 `localStorage` 캐시는 정본의 사본일 뿐 원본이 아니다.
- **GDrive `copier_메모리`**: 사람이 읽는 정리본(거울). 정본을 여기에 수동 복사하지 않는다.

로딩 순서: ① `registry.bundle.js` 스냅샷(웹 접근 가능한 정본 전달본) → ② (번들이 없을 때만) 로컬 캐시 → ③ 초안을 별도 레이어로 오버레이. `data/`가 웹 차단이라 `registry.json` HTTP fetch는 사용하지 않는다.

`localStorage` 키:
- `copier.componentLibrary.canonicalCache.v1` — 최근 fetch한 정본 캐시
- `copier.componentLibrary.drafts.v1` — 로컬 초안
- `copier.componentLibrary.legacyMigrated.v1` — 이전 playground 카드 이관 여부(원본 `copier_pg_v1`은 삭제하지 않음)

---

## 3. 컴포넌트 스키마

```json
{
  "schema_version": 1,
  "id": "autocomplete-floating-panel",
  "title": "떠있는 자동완성 목록",
  "category": "input",
  "summary": "한 줄 설명",
  "tags": ["autocomplete", "keyboard", "자동완성"],
  "status": "active",
  "aliases": [],
  "html": "...",
  "css": "...",
  "js": "...",
  "dependencies": [],
  "project_refs": ["stockio.php"],
  "source": {
    "type": "project",
    "project_path": "stockio.php",
    "url": null,
    "author": "CopierRMS",
    "license": "project-internal",
    "attribution": "출처 설명",
    "adaptation_notes": "변경/각색 메모"
  },
  "verified": false,
  "verification": { "verified_at": null, "verified_by": [], "checks": [] },
  "usage_notes": [],
  "known_pitfalls": [],
  "related_components": [],
  "supersedes": null,
  "revision": 1,
  "created_at": "2026-07-16",
  "updated_at": "2026-07-16"
}
```

### 규칙
- **id**: 최초 커밋 이후 불변. 소문자 kebab-case `^[a-z0-9]+(?:-[a-z0-9]+)*$`. 날짜형·`card-1` 같은 무의미 이름 금지. 이름이 바뀌면 `aliases`에 옛 id, 대체하면 `supersedes`에 옛 id.
- **category**: 아래 고정 enum 중 하나(즉흥 추가 금지). id는 category와 무관하게 **전역 고유**.
  - `action` · `navigation` · `input` · `data-display` · `feedback` · `overlay` · `layout` · `utility`
- **tags**: 최대 12개, 중복 금지, 영문 기능 태그 2개 이상 포함, `ui`·`component` 같은 무의미 태그만 넣지 않기.
- **source**: 외부 코드면 `url`·`author`·`license`를 반드시 기록. 라이선스 불명은 `verified=false`이며 프로젝트 재사용 금지.
- **verified**: 기본 `false`. 아래를 모두 만족한 뒤에만 `true`.
  - JSON/스키마 검증 통과 · sandbox 미리보기 콘솔 오류 없음 · 키보드/마우스/모바일/라이트·다크 확인 · `UI_GUIDE.md` 및 실제 CSS 변수와 정합 · 민감정보 없음 · (외부면) 출처·라이선스 확인 · Codex 리뷰 + 사용자 화면 확인.

---

## 4. 사람이 UI에서 추가하는 흐름

1. 플레이그라운드 `[＋ 새 초안]`에서 코드 작성(실시간 미리보기로 확인).
2. `[초안 저장]` → `localStorage` 초안으로 보관(정본 아님).
3. `[내보내기]`(초안 전체) 또는 카드의 `[내보내기]`로 `{id}.component.json` 기여 파일 다운로드.
4. 그 파일을 **Claude에게 주고 정본 반영 요청**.
5. Claude가 기존 정본을 검색·비교 후 `registry.json`에 반영하고 `registry.bundle.js` 재생성.
6. dev_center Git diff 확인 → 사용자 지시 시 선택 커밋.

> 브라우저는 정본 파일이나 Git에 직접 쓰지 않는다. MVP에는 저장용 서버 엔드포인트가 없다.

---

## 5. AI 기여 프로토콜 (Claude·Codex 공통)

새 UI를 만들기 전에 **id·aliases·제목·태그·project_refs를 먼저 검색**한다.

1. 목적이 같은 기존 항목이 있으면 새로 만들지 말고 그 항목을 갱신.
2. 동작이 다르거나 하위호환이 깨지면 **새 id**로 만들고 `related_components`/`supersedes` 기록.
3. **Claude만** 승인된 범위에서 `registry.json`(및 파생물)을 수정. **Codex는 파일을 직접 수정하지 않고** 중복·범위·XSS 격리·출처·diff를 리뷰.
4. 스키마 검증(아래 명령)을 통과시키고, `registry.bundle.js`가 `registry.json`과 일치하게 재생성.
5. 새 항목은 `verified=false`로 시작 → 검증·리뷰·사용자 확인 후에만 `true`.
6. 해당 파일만 선택 커밋(`git add -A` 금지). 변경 요약은 GDrive `03_작업이력/작업로그.md`에 남긴다.

### 검증 + 번들 재생성 명령 (registry.json 편집 후 필수)

`data/component_library/`에서 실행. `registry.json`을 검증하고 `component_playground/registry.bundle.js`를 동일 객체로 다시 만든다.

```bash
php -r '
$d=json_decode(file_get_contents("registry.json"),true,512,JSON_THROW_ON_ERROR);
if(($d["schema_version"]??null)!==1) throw new Exception("schema_version");
if(!is_array($d["components"]??null)) throw new Exception("components");
$ids=array_column($d["components"],"id");
if(count($ids)!==count(array_unique($ids))) throw new Exception("dup id");
$cats=["action","navigation","input","data-display","feedback","overlay","layout","utility"];
foreach($d["components"] as $c){
  if(!preg_match("/^[a-z0-9]+(?:-[a-z0-9]+)*$/",$c["id"])) throw new Exception("bad id ".$c["id"]);
  if(!in_array($c["category"],$cats,true)) throw new Exception("bad cat ".$c["category"]);
}
$j=json_encode($d,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
file_put_contents("../../component_playground/registry.bundle.js",
  "/* 자동 생성물 — registry.json 스냅샷. 직접 수정 금지. */\nwindow.COPIER_COMPONENT_REGISTRY = ".$j.";\n");
echo "OK ".count($d["components"])."\n";
'
```

(빌드 스크립트는 Phase 2에서 `scripts/build_component_registry.php`로 정식화한다.)

---

## 6. 보안·주의

- 미리보기는 `iframe srcdoc` + `sandbox="allow-scripts allow-modals"`(같은 출처·폼·팝업·상위 이동 불가) + 제한 CSP(`default-src 'none'`)로 외부 통신·프레임·폼을 차단한다.
- 제목·태그·출처 등 메타데이터는 `innerHTML`이 아니라 `textContent`로 출력(주입 방지).
- 예제에 실제 거래처명·IP·토큰·config 값·API 키를 넣지 않는다.
- 파일은 UTF-8(BOM 없음), 들여쓰기 2칸. PowerShell 기본 인코딩으로 JSON을 다시 저장하지 않는다.
- CDN·원격 폰트·외부 JS를 `verified` 컴포넌트의 필수 의존성으로 허용하지 않는다.

---

## 7. 로드맵

- **Phase 1 (현재/MVP)**: 단일 `registry.json` 정본 + 번들 fallback + 초안/기여 흐름. PHP·MySQL 없음.
- **Phase 2**: `components/{id}.json` 분리 + `component.schema.json` + `build_component_registry.php`(검증·정렬·집계·번들 생성). 이후 `registry.json`은 직접 수정하지 않음.
- **Phase 3(선택)**: 로컬 전용 PHP `inbox/` 기여함(정본 아님, 리뷰 후 승격).
- **Phase 4**: 기존 자산(GDrive 예제·copier 검증 패턴)을 `verified=false`로 가져와 하나씩 검증하고 INDEX/ai_memory/AGENTS.md에 등록.
