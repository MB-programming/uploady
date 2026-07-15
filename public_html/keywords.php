<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireLogin();

$errors = [];
$results = null;       // [keyword => demand score 1-100]
$competition = [];     // [keyword => total competing videos] (YouTube only)
$seed = '';
$platform = 'youtube';
$hasYouTubeAccount = false;

$youtubeAccounts = array_values(array_filter(
    SocialAccount::forUser(Auth::id()),
    fn ($a) => $a['platform'] === 'youtube'
));
$hasYouTubeAccount = $youtubeAccounts !== [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();

    $seed = trim((string) ($_POST['seed'] ?? ''));
    $platform = in_array($_POST['platform'] ?? '', ['youtube', 'tiktok', 'instagram'], true)
        ? $_POST['platform']
        : 'youtube';

    if (mb_strlen($seed) < 2) {
        $errors[] = t('keywords.err_seed_required');
    }

    if (!$errors) {
        $results = KeywordService::suggestions($seed, $platform, Lang::locale());
        if ($results === []) {
            $errors[] = t('keywords.err_no_results');
            $results = null;
        } elseif ($platform === 'youtube' && $hasYouTubeAccount) {
            $competition = KeywordService::youtubeCompetition(array_keys($results), $youtubeAccounts[0]);
        }
    }
}

$pageTitle = t('nav.keywords');
require __DIR__ . '/partials_header.php';
?>
<h1><?= t('keywords.title') ?></h1>
<p class="muted"><?= t('keywords.intro') ?></p>

<?php foreach ($errors as $error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
<?php endforeach; ?>

<form method="post" class="card" style="max-width:560px;">
    <?= Csrf::field() ?>

    <label><?= t('keywords.seed_label') ?></label>
    <input type="text" name="seed" value="<?= htmlspecialchars($seed) ?>" required minlength="2" placeholder="<?= t('keywords.seed_placeholder') ?>">

    <label><?= t('common.platform') ?></label>
    <select name="platform">
        <option value="youtube" <?= $platform === 'youtube' ? 'selected' : '' ?>><?= t('platform.youtube') ?></option>
        <option value="tiktok" <?= $platform === 'tiktok' ? 'selected' : '' ?>><?= t('platform.tiktok') ?></option>
        <option value="instagram" <?= $platform === 'instagram' ? 'selected' : '' ?>><?= t('platform.instagram') ?></option>
    </select>

    <p><button type="submit" class="btn" style="margin-top:20px;"><?= t('keywords.generate') ?></button></p>
</form>

<?php if ($results !== null): ?>
    <?php
    $keywords = array_keys($results);
    $hashtags = array_map([KeywordService::class, 'toHashtag'], $keywords);
    $levelBadge = ['low' => 'published', 'medium' => 'pending', 'high' => 'failed'];
    ?>

    <h2 style="font-size:16px;"><?= sprintf(t('keywords.results_title'), htmlspecialchars($seed)) ?></h2>

    <?php if ($platform === 'youtube' && !$hasYouTubeAccount): ?>
        <div class="alert error" style="background:transparent;"><?= t('keywords.connect_youtube_hint') ?></div>
    <?php elseif ($platform !== 'youtube'): ?>
        <p class="muted" style="font-size:13px;"><?= t('keywords.no_competition_platform') ?></p>
    <?php endif; ?>

    <div class="card" style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th><?= t('keywords.col_keyword') ?></th>
                    <th><?= t('keywords.col_hashtag') ?></th>
                    <th><?= t('keywords.col_demand') ?></th>
                    <th><?= t('keywords.col_competition') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $keyword => $score): ?>
                    <?php
                    $count = $competition[$keyword] ?? null;
                    $level = $count !== null ? KeywordService::competitionLevel($count) : null;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($keyword) ?></td>
                        <td dir="ltr"><?= htmlspecialchars(KeywordService::toHashtag($keyword)) ?></td>
                        <td><?= (int) $score ?>/100</td>
                        <td>
                            <?php if ($level === null || $level === 'unknown'): ?>
                                <span class="muted">—</span>
                            <?php else: ?>
                                <span class="badge <?= $levelBadge[$level] ?>"><?= t('keywords.level_' . $level) ?></span>
                                <span class="muted" style="font-size:12px;white-space:nowrap;"><?= sprintf(t('keywords.videos_count'), number_format($count)) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <label><?= t('keywords.copy_keywords_label') ?></label>
        <p class="muted" style="margin:4px 0 10px;word-break:break-word;"><?= htmlspecialchars(implode(', ', $keywords)) ?></p>
        <button type="button" class="btn secondary" data-copy="<?= htmlspecialchars(implode(', ', $keywords)) ?>" data-copied-label="<?= t('keywords.copied') ?>"><?= t('keywords.copy') ?></button>

        <label style="margin-top:18px;"><?= t('keywords.copy_hashtags_label') ?></label>
        <p class="muted" style="margin:4px 0 10px;word-break:break-word;" dir="ltr"><?= htmlspecialchars(implode(' ', $hashtags)) ?></p>
        <button type="button" class="btn secondary" data-copy="<?= htmlspecialchars(implode(' ', $hashtags)) ?>" data-copied-label="<?= t('keywords.copied') ?>"><?= t('keywords.copy') ?></button>
    </div>

    <p class="muted" style="font-size:13px;"><?= t('keywords.methodology') ?></p>
<?php endif; ?>

<script src="assets/js/keywords.js"></script>
<?php require __DIR__ . '/partials_footer.php'; ?>
