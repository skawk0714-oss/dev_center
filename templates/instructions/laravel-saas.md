## 7. Laravel SaaS 특화 규칙

- Artisan 명령은 `php artisan` 으로 실행한다.
- DB 변경은 반드시 Migration 파일로 관리한다. 스키마를 직접 수정하지 않는다.
- `.env` 의 민감정보는 코드에 하드코딩하지 않는다.
- 멀티테넌트 구조라면 tenant scope 를 반드시 확인한다.
- API 응답은 JSON Resource 또는 API Resource 클래스를 사용한다.
- 테스트는 PHPUnit / Pest 중 이미 사용 중인 방식을 따른다.

### 초기 작업
{{INITIAL_TASK}}
