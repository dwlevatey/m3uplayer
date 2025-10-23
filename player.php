<?php
require_once 'middleware/auth.php';
require_client_auth();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M3U Streaming - Player</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/player.css">
</head>
<body>
    <!-- Header -->
    <header class="player-header">
        <div class="header-content">
            <div class="header-left">
                <h1 class="logo">M3U Streaming</h1>
                <nav class="main-nav">
                    <a href="#" class="nav-link active" data-view="home">Início</a>
                    <a href="#" class="nav-link" data-view="categories">Categorias</a>
                    <a href="#" class="nav-link" data-view="favorites">Favoritos</a>
                </nav>
            </div>
            <div class="header-right">
                <div class="search-container">
                    <input type="text" id="searchInput" placeholder="Buscar canais..." class="search-input">
                    <button class="search-btn">🔍</button>
                </div>
                <div class="user-menu">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['client_username']); ?></span>
                    <button class="logout-btn" onclick="logout()">Sair</button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="player-main">
        <!-- Loading State -->
        <div id="loadingState" class="loading-state">
            <div class="spinner"></div>
            <p>Carregando canais...</p>
        </div>

        <!-- Error State -->
        <div id="errorState" class="error-state" style="display: none;">
            <div class="error-icon">⚠️</div>
            <h2>Erro ao carregar canais</h2>
            <p id="errorMessage"></p>
            <button class="btn-primary" onclick="loadChannels()">Tentar Novamente</button>
        </div>

        <!-- Home View -->
        <div id="homeView" class="content-view" style="display: none;">
            <!-- Featured Channel -->
            <div id="featuredChannel" class="featured-section"></div>

            <!-- Categories -->
            <div id="categoriesContainer" class="categories-container"></div>
        </div>

        <!-- Categories View -->
        <div id="categoriesView" class="content-view" style="display: none;">
            <h2 class="section-title">Todas as Categorias</h2>
            <div id="allCategoriesGrid" class="categories-grid"></div>
        </div>

        <!-- Search Results -->
        <div id="searchResults" class="content-view" style="display: none;">
            <h2 class="section-title">Resultados da Busca</h2>
            <div id="searchResultsGrid" class="channels-grid"></div>
        </div>
    </main>

    <!-- Video Player Modal -->
    <div id="playerModal" class="player-modal" style="display: none;">
        <div class="player-modal-content">
            <button class="player-close" onclick="closePlayer()">&times;</button>
            <div class="player-info">
                <h2 id="playerChannelName"></h2>
                <span id="playerChannelCategory"></span>
            </div>
            <div class="video-container">
                <video id="videoPlayer" controls autoplay></video>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <script src="assets/js/player.js"></script>
</body>
</html>
