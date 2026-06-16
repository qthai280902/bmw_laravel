# Bug Index

| ID | Ten loi | Khu vuc | Trang thai | File chi tiet | Ghi chu |
|---|---|---|---|---|---|
| BUG-BUILD-001 | `npm run build` loi PowerShell ExecutionPolicy | build/frontend | Workaround | `build/npm-build.md` | Dung `npm.cmd run build`. |
| BUG-TEST-001 | Full suite `php artisan test` fail auth/register/settings cu | test/backend | Fixed / Verified | `build/php-artisan-test.md` | Tests da dong bo route hien tai; Phase 13 full suite pass 54 tests / 669 assertions. |
| BUG-CI-001 | GitHub Actions `Verify & Test` fail sau rename repo | ci/test | Fixed / Verified local | `build/github-actions-verify-test.md` | Root cause la full suite test cu; remote local da doi sang repo moi. |
| BUG-RUNTIME-001 | Storage junction cu va admin image URL raw gay request `/storage/...` hong | runtime/assets | Fixed / Verified | `fixed/phase-13-fixed-bugs.md` | Phase 13 recreate storage link va admin product views dung `displayImageUrl()`. |
| BUG-COMPARE-001 | Compare raw IDs khong preserve order/limit server-side | public/compare | Fixed / Verified | `fixed/phase-16-1-compare-id-normalization.md` | Phase 16.1 normalize IDs, ignore invalid/accessory, preserve order, max 4 vehicles. |
| BUG-AI-001 | AI context/matching bo sot BMW Motorrad va model code ngan | ai/context | Fixed / Verified | `fixed/phase-16-2-ai-context-product-matching.md` | Phase 16.2 expands public context and adds aliases like `330i`, `530i`, `s1000rr`. |
