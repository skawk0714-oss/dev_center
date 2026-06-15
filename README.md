# 개발센터 (Dev Center)

CopierRMS 개발 전용 로컬 허브. 프로덕션 ERP 메뉴와 완전히 분리된 독립 PHP 프로젝트.

## 접속

```
http://localhost/dev_center
```

## 구조

```
dev_center/
├── index.php          # 대시보드 (인증 불필요, 로컬 전용)
├── config.php         # 경로·상수 정의
├── knowledge.php      # 기능 보관함 (TODO)
├── projects.php       # 프로젝트 센터 (TODO)
├── lab.php            # 실험실 (TODO)
├── prompts.php        # 프롬프트 관리 (TODO)
├── assets/css/
│   └── dev_center.css
└── data/
    ├── features.json  # 기능 보관함 인덱스 (copier/docs/knowledge 복사본)
    ├── projects.json  # 프로젝트 목록 (copier/docs/project-center 복사본)
    └── records/       # 기능 상세 기록 JSON
```

## 릴리즈 노트

## 1.1.0 - 2026-06-15

### 추가
- 메뉴별 AI 작업실 정의와 역할 프롬프트를 추가했습니다.
- 각 메뉴에서 AI 프롬프트를 클립보드에 복사할 수 있게 했습니다.
- Codex/Claude 실행 버튼과 VSCode 프로젝트 열기 버튼을 추가했습니다.

### 개선
- AI 작업실 버튼 배치와 시각적 구분(VSCode 버튼 아웃라인 스타일)을 정리했습니다.
- 런처 응답 JSON이 항상 정상 반환되도록 안정화했습니다.
- 헤더 액션 버튼이 우측 정렬되고 모바일에서 자연스럽게 줄바꿈되도록 개선했습니다.

---

## 주의

- 이 프로젝트는 **외부 공개 금지** — 로컬 개발 환경 전용
- CopierRMS(`copier/`) 인증 시스템에 의존하지 않음
- `data/`의 JSON은 CopierRMS 원본과 **동기화되지 않음** — 필요할 때 수동 복사
