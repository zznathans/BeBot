## 1.6.4 (2026-08-02)

## [1.6.4](https://github.com/zznathans/BeBot/compare/1.6.3...1.6.4) (2026-08-02)


### Bug Fixes

* **relay:** fix undefined-array-key warning in autoinvite's security-group path ([88f0a4c](https://github.com/zznathans/BeBot/commit/88f0a4c4201e5544bc2aa68e74b8ebc210dfe955)), closes [#___online](https://github.com/zznathans/BeBot/issues/___online)





## 1.6.3 (2026-08-02)

## [1.6.3](https://github.com/zznathans/BeBot/compare/1.6.2...1.6.3) (2026-08-02)


### Bug Fixes

* **ci:** remove the dead bump-nightly job from docker-dev.yml ([b7f1f81](https://github.com/zznathans/BeBot/commit/b7f1f817b51228b5fb14319028534e8f193bf61b))





## 1.6.2 (2026-08-02)

## [1.6.2](https://github.com/zznathans/BeBot/compare/1.6.1...1.6.2) (2026-08-02)


### Bug Fixes

* **items:** default Items.CIDB to the new self-hosted aodb-api ([5fd546a](https://github.com/zznathans/BeBot/commit/5fd546a3b112bca07657ec0b87b6e042e99a2e93))





## 1.6.1 (2026-08-02)

## [1.6.1](https://github.com/zznathans/BeBot/compare/1.6.0...1.6.1) (2026-08-02)


### Bug Fixes

* **timer:** make internal timer duplicates a DB-level impossibility ([b54cc6d](https://github.com/zznathans/BeBot/commit/b54cc6d55346641f9a60d1f28504ea578792819e))





## 1.6.0 (2026-08-01)

# [1.6.0](https://github.com/zznathans/BeBot/compare/1.5.0...1.6.0) (2026-08-01)


### Features

* **ci:** nightly scheduled dev build, auto-bump bebot-nightly ([55fc44e](https://github.com/zznathans/BeBot/commit/55fc44e97d70788e519de96b887847e3fe1cf742))





## 1.5.0 (2026-08-01)

# [1.5.0](https://github.com/zznathans/BeBot/compare/1.4.1...1.5.0) (2026-08-01)


### Features

* **ci:** add a label for every individual module/component ([f548055](https://github.com/zznathans/BeBot/commit/f54805532b050a6efdcf0d9b3ae2a740ee9e5b42))
* **ci:** add PR labeler ([e717c9f](https://github.com/zznathans/BeBot/commit/e717c9f6185981bceddf7b93d755079a9b3b225d))





## 1.4.1 (2026-08-01)

## [1.4.1](https://github.com/zznathans/BeBot/compare/1.4.0...1.4.1) (2026-08-01)


### Bug Fixes

* **ci:** sync gh-pages via static shells, not broken Jekyll front matter ([04592ea](https://github.com/zznathans/BeBot/commit/04592ea3cd374ec867f8fcadd07ad27e3f262153)), closes [#pages](https://github.com/zznathans/BeBot/issues/pages)





## 1.4.0 (2026-08-01)

# [1.4.0](https://github.com/zznathans/BeBot/compare/1.3.0...1.4.0) (2026-08-01)


### Features

* **ci:** dark-theme gh-pages, sync any README it links to ([bd069c9](https://github.com/zznathans/BeBot/commit/bd069c99406a71cb101afa74c2ab3bfa8cfcc847)), closes [#pages](https://github.com/zznathans/BeBot/issues/pages) [#pages](https://github.com/zznathans/BeBot/issues/pages) [#pages](https://github.com/zznathans/BeBot/issues/pages) [#pages](https://github.com/zznathans/BeBot/issues/pages)





## 1.3.0 (2026-08-01)

# [1.3.0](https://github.com/zznathans/BeBot/compare/1.2.0...1.3.0) (2026-08-01)


### Features

* **ci:** keep gh-pages README in sync with main automatically ([de0255d](https://github.com/zznathans/BeBot/commit/de0255d41381ef4f0a7e9a57c889332b4ec428f4)), closes [#pages](https://github.com/zznathans/BeBot/issues/pages) [#pages](https://github.com/zznathans/BeBot/issues/pages) [#pages](https://github.com/zznathans/BeBot/issues/pages)





## 1.2.0 (2026-08-01)

# [1.2.0](https://github.com/zznathans/BeBot/compare/1.1.0...1.2.0) (2026-08-01)


### Bug Fixes

* comment in generate-readme.php was closing itself early ([62cb14e](https://github.com/zznathans/BeBot/commit/62cb14ef4e19b557a651b367cc5549ce2f80c121))
* **generate-readme:** use the whole match's offset, not the capture group's ([d78696d](https://github.com/zznathans/BeBot/commit/d78696d09a970d467821a9691f441b676125bf30))


### Features

* add a pre-commit hook to keep Tests/README.md in sync ([a237c83](https://github.com/zznathans/BeBot/commit/a237c833db3de61d312987055d230a269bb6b2dc))





## 1.1.0 (2026-08-01)

# [1.1.0](https://github.com/zznathans/BeBot/compare/1.0.0...1.1.0) (2026-08-01)


### Bug Fixes

* **ci:** stop releases from fighting main's new branch ruleset ([14a0eab](https://github.com/zznathans/BeBot/commit/14a0eab8f0aaa69d41030bfb19c3295022bc184d))


### Features

* own the Docker image build, move it out of bebot-helm ([a9b66c9](https://github.com/zznathans/BeBot/commit/a9b66c9e3ca1364eb4a2653e7159dca5ecc079f4))





# 1.0.0 (2026-08-01)


### Bug Fixes

* **access-control:** make primary key migration atomic ([573d8ee](https://github.com/zznathans/BeBot/commit/573d8ee6acd45dc3fdd4113cbe92d7b0f0c1f613)), closes [#___access_control](https://github.com/zznathans/BeBot/issues/___access_control)
* **bot:** cache nickname->uid lookups in get_db_uid() ([9b0a2df](https://github.com/zznathans/BeBot/commit/9b0a2df113512ecc735535a6dbd6bbb980f9dc79)), closes [#___users](https://github.com/zznathans/BeBot/issues/___users) [#___users](https://github.com/zznathans/BeBot/issues/___users)
* **bot:** stop ConsoleLogHandler from corrupting JSON log lines ([286b177](https://github.com/zznathans/BeBot/commit/286b1777e07f4778e10ef6ab675b57bb6d3fdf8d))
* **data:** switch Symbiants seed tables from MyISAM to InnoDB ([3a54cba](https://github.com/zznathans/BeBot/commit/3a54cba60c87ad3ede8da3e2cd965be001a06565))
* **log:** stop DbLogHandler from permanently capturing a null db ([821039e](https://github.com/zznathans/BeBot/commit/821039e4109a4fd9d2ac777a5a8066f22053cc02))
* **log:** stop LogDispatcher recursing forever on a bot's first boot ([b3962d8](https://github.com/zznathans/BeBot/commit/b3962d8e7336e7db136211b89126d2cf3ea72566))
* **market:** make Market-Poll/Market-AutoTrack timer setup self-healing ([a76f210](https://github.com/zznathans/BeBot/commit/a76f2106b29f4b3ac5c6c2d6f512f3549cefebca))


### Features

* add Market and Advertise modules ([4d5f8d9](https://github.com/zznathans/BeBot/commit/4d5f8d99679f097bf930a493bec8dc9128f2c9b8))
* **bot:** add optional Redis-backed cache, wire into get_db_uid() ([20530e0](https://github.com/zznathans/BeBot/commit/20530e0557b0cbefdec14f3704e77fb35804084e)), closes [#___users](https://github.com/zznathans/BeBot/issues/___users)
* **ci:** add semantic-release + bebot-helm notify pipeline ([5261fe0](https://github.com/zznathans/BeBot/commit/5261fe0277c82de36f752453cae11672b0ae8100))
* **log:** seed Log.*Format settings from Bot.conf on first boot ([1c69008](https://github.com/zznathans/BeBot/commit/1c690089a3071913b4eb91951fa72b01f53f1ff4))
* **market:** add admin market untrack all ([4b82afc](https://github.com/zznathans/BeBot/commit/4b82afcd0e4092b7b6eda2f9f23576407cd0c0ee)), closes [#___market_watch](https://github.com/zznathans/BeBot/issues/___market_watch)
* **market:** bulk unwatch, fix stale help text ([535210f](https://github.com/zznathans/BeBot/commit/535210f8a8c5d5cfb86074a9b3011a219f6544cd))
* **market:** log background item update tasks to the private channel ([7dc3c8d](https://github.com/zznathans/BeBot/commit/7dc3c8dc0627f3cfe059bfd24599736d01e88379))
* **market:** price/QL filters on watched items ([c35772e](https://github.com/zznathans/BeBot/commit/c35772e510e93768e18a2e00fc7baab597ef170c))
* **mysql:** support a configurable port and SSL/TLS connections ([751aca8](https://github.com/zznathans/BeBot/commit/751aca8a0e60a18e0480ae2f1bb093fb4c83b328))
