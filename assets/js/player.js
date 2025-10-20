let allChannels = []
let groupedChannels = {}
let categories = []
let currentView = "home"

// Initialize
document.addEventListener("DOMContentLoaded", () => {
  loadChannels()
  setupEventListeners()
  setupScrollHeader()

  const modal = document.getElementById("playerModal")

  modal.addEventListener("click", (e) => {
    // Close if clicking on the modal background (not the content)
    if (e.target === modal) {
      closePlayer()
    }
  })
})

// Setup event listeners
function setupEventListeners() {
  // Navigation
  document.querySelectorAll(".nav-link").forEach((link) => {
    link.addEventListener("click", function (e) {
      e.preventDefault()
      const view = this.dataset.view

      // Update active state
      document.querySelectorAll(".nav-link").forEach((l) => l.classList.remove("active"))
      this.classList.add("active")

      // Show view
      showView(view)
    })
  })

  // Search
  const searchInput = document.getElementById("searchInput")
  const searchBtn = document.querySelector(".search-btn")

  searchBtn.addEventListener("click", performSearch)
  searchInput.addEventListener("keypress", (e) => {
    if (e.key === "Enter") {
      performSearch()
    }
  })
}

// Setup scroll header
function setupScrollHeader() {
  let lastScroll = 0
  const header = document.querySelector(".player-header")

  window.addEventListener("scroll", () => {
    const currentScroll = window.pageYOffset

    if (currentScroll > 100) {
      header.classList.add("scrolled")
    } else {
      header.classList.remove("scrolled")
    }

    lastScroll = currentScroll
  })
}

// Load channels
async function loadChannels() {
  showLoading()

  try {
    console.log("[v0] Carregando canais...")
    const response = await fetch("api/get-channels.php")
    console.log("[v0] Resposta recebida:", response.status, response.statusText)

    const data = await response.json()
    console.log("[v0] Dados recebidos:", data)

    if (data.success) {
      allChannels = data.data.channels
      groupedChannels = data.data.grouped
      categories = data.data.categories

      console.log("[v0] Canais carregados com sucesso:", allChannels.length)
      showView("home")
    } else {
      console.error("[v0] Erro ao carregar canais:", data.message)
      console.error("[v0] Debug info:", data.debug)
      showError(data.message)
    }
  } catch (error) {
    console.error("[v0] Erro ao conectar com o servidor:", error)
    showError("Erro ao conectar com o servidor")
  }
}

// Show view
function showView(view) {
  currentView = view

  // Hide all views
  document.querySelectorAll(".content-view").forEach((v) => {
    v.style.display = "none"
  })
  document.getElementById("loadingState").style.display = "none"
  document.getElementById("errorState").style.display = "none"

  // Show selected view
  switch (view) {
    case "home":
      renderHomeView()
      break
    case "categories":
      renderCategoriesView()
      break
    case "favorites":
      renderFavoritesView()
      break
  }
}

// Render home view
function renderHomeView() {
  const homeView = document.getElementById("homeView")
  homeView.style.display = "block"

  // Render featured channel
  if (allChannels.length > 0) {
    const featured = allChannels[0]
    renderFeaturedChannel(featured)
  }

  // Render categories with content type detection
  const container = document.getElementById("categoriesContainer")
  container.innerHTML = ""

  // Separate categories by type
  const movieCategories = categories.filter(
    (cat) =>
      cat.toLowerCase().includes("filme") ||
      cat.toLowerCase().includes("movie") ||
      cat.toLowerCase().includes("cinema"),
  )

  const seriesCategories = categories.filter(
    (cat) =>
      cat.toLowerCase().includes("série") ||
      cat.toLowerCase().includes("series") ||
      cat.toLowerCase().includes("novela"),
  )

  const channelCategories = categories.filter(
    (cat) => !movieCategories.includes(cat) && !seriesCategories.includes(cat),
  )

  // Render Channels section
  if (channelCategories.length > 0) {
    const section = createCategorySection("📺 Canais ao Vivo", channelCategories.slice(0, 3))
    container.appendChild(section)
  }

  // Render Movies section
  if (movieCategories.length > 0) {
    const section = createCategorySection("🎬 Filmes", movieCategories.slice(0, 2))
    container.appendChild(section)
  }

  // Render Series section
  if (seriesCategories.length > 0) {
    const section = createCategorySection("📺 Séries", seriesCategories.slice(0, 2))
    container.appendChild(section)
  }

  // If no specific categories found, show first 5 categories
  if (channelCategories.length === 0 && movieCategories.length === 0 && seriesCategories.length === 0) {
    categories.slice(0, 5).forEach((category) => {
      const section = createCategorySection(category, [category])
      container.appendChild(section)
    })
  }
}

function createCategorySection(title, categoryList) {
  const wrapper = document.createElement("div")
  wrapper.className = "content-type-section"

  const sectionTitle = document.createElement("h2")
  sectionTitle.className = "content-type-title"
  sectionTitle.textContent = title
  wrapper.appendChild(sectionTitle)

  categoryList.forEach((category) => {
    const section = document.createElement("div")
    section.className = "category-section"

    const categoryHeader = document.createElement("div")
    categoryHeader.className = "category-header"

    const categoryTitle = document.createElement("h3")
    categoryTitle.className = "category-title"
    categoryTitle.textContent = category

    const viewAllBtn = document.createElement("button")
    viewAllBtn.className = "view-all-btn"
    viewAllBtn.textContent = "Ver todos"
    viewAllBtn.onclick = () => showCategoryChannels(category)

    categoryHeader.appendChild(categoryTitle)
    categoryHeader.appendChild(viewAllBtn)

    const row = document.createElement("div")
    row.className = "channels-row"

    const categoryChannels = groupedChannels[category] || []
    categoryChannels.slice(0, 10).forEach((channel) => {
      row.appendChild(createChannelCard(channel))
    })

    section.appendChild(categoryHeader)
    section.appendChild(row)
    wrapper.appendChild(section)
  })

  return wrapper
}

// Render featured channel
function renderFeaturedChannel(channel) {
  const featured = document.getElementById("featuredChannel")
  featured.innerHTML = `
    <div class="featured-overlay"></div>
    <div class="featured-content">
      <h2 class="featured-logo">${escapeHtml(channel.name)}</h2>
      <p class="featured-description">${escapeHtml(channel.category)}</p>
      <div class="featured-actions">
        <button class="btn-play" onclick='playChannel(${JSON.stringify(channel)})'>
          ▶ Assistir
        </button>
      </div>
    </div>
  `

  if (channel.logo) {
    featured.style.backgroundImage = `url(${channel.logo})`
  }
}

// Render categories view
function renderCategoriesView() {
  const view = document.getElementById("categoriesView")
  view.style.display = "block"

  const grid = document.getElementById("allCategoriesGrid")
  grid.innerHTML = ""

  categories.forEach((category) => {
    const count = groupedChannels[category]?.length || 0
    const card = document.createElement("div")
    card.className = "category-card"
    card.innerHTML = `
      <h3>${escapeHtml(category)}</h3>
      <p>${count} ${count === 1 ? "canal" : "canais"}</p>
    `
    card.onclick = () => showCategoryChannels(category)
    grid.appendChild(card)
  })
}

// Show category channels
function showCategoryChannels(category) {
  const view = document.getElementById("searchResults")
  view.style.display = "block"

  document.getElementById("homeView").style.display = "none"
  document.getElementById("categoriesView").style.display = "none"

  const title = view.querySelector(".section-title")
  title.textContent = category

  const grid = document.getElementById("searchResultsGrid")
  grid.innerHTML = ""

  const categoryChannels = groupedChannels[category] || []
  categoryChannels.forEach((channel) => {
    grid.appendChild(createChannelCard(channel))
  })
}

// Render favorites view
function renderFavoritesView() {
  const view = document.getElementById("searchResults")
  view.style.display = "block"

  const title = view.querySelector(".section-title")
  title.textContent = "Favoritos"

  const grid = document.getElementById("searchResultsGrid")
  grid.innerHTML = `
    <div style="grid-column: 1 / -1; text-align: center; padding: 48px; color: var(--text-secondary);">
      <p>Funcionalidade de favoritos em desenvolvimento</p>
    </div>
  `
}

// Perform search
async function performSearch() {
  const query = document.getElementById("searchInput").value.trim()

  if (!query) return

  try {
    const response = await fetch(`api/search-channels.php?q=${encodeURIComponent(query)}`)
    const data = await response.json()

    if (data.success) {
      showSearchResults(data.data.channels, query)
    }
  } catch (error) {
    console.error("[v0] Search error:", error)
  }
}

// Show search results
function showSearchResults(channels, query) {
  const view = document.getElementById("searchResults")
  view.style.display = "block"

  document.getElementById("homeView").style.display = "none"
  document.getElementById("categoriesView").style.display = "none"

  const title = view.querySelector(".section-title")
  title.textContent = `Resultados para "${query}"`

  const grid = document.getElementById("searchResultsGrid")
  grid.innerHTML = ""

  if (channels.length === 0) {
    grid.innerHTML = `
      <div style="grid-column: 1 / -1; text-align: center; padding: 48px; color: var(--text-secondary);">
        <p>Nenhum canal encontrado</p>
      </div>
    `
    return
  }

  channels.forEach((channel) => {
    grid.appendChild(createChannelCard(channel))
  })
}

// Improved create channel card with better image error handling
function createChannelCard(channel) {
  const card = document.createElement("div")
  card.className = "channel-card"
  card.onclick = () => playChannel(channel)

  // Better image handling with multiple fallbacks
  let thumbnailHTML = ""
  if (channel.logo && channel.logo.trim() !== "") {
    thumbnailHTML = `
      <img 
        src="${channel.logo}" 
        alt="${escapeHtml(channel.name)}" 
        loading="lazy"
        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'"
      >
      <div class="channel-thumbnail-placeholder" style="display: none;">
        ${getChannelIcon(channel.category)}
      </div>
    `
  } else {
    thumbnailHTML = `
      <div class="channel-thumbnail-placeholder">
        ${getChannelIcon(channel.category)}
      </div>
    `
  }

  card.innerHTML = `
    <div class="channel-thumbnail">
      ${thumbnailHTML}
      <div class="play-overlay">
        <div class="play-icon">▶</div>
      </div>
    </div>
    <div class="channel-info">
      <div class="channel-name" title="${escapeHtml(channel.name)}">${escapeHtml(channel.name)}</div>
      <div class="channel-category">${escapeHtml(channel.category)}</div>
    </div>
  `

  return card
}

function getChannelIcon(category) {
  const cat = category.toLowerCase()

  if (cat.includes("filme") || cat.includes("movie") || cat.includes("cinema")) {
    return "🎬"
  } else if (cat.includes("série") || cat.includes("series") || cat.includes("novela")) {
    return "📺"
  } else if (cat.includes("esporte") || cat.includes("sport")) {
    return "⚽"
  } else if (cat.includes("notícia") || cat.includes("news")) {
    return "📰"
  } else if (cat.includes("infantil") || cat.includes("kids") || cat.includes("desenho")) {
    return "🎨"
  } else if (cat.includes("música") || cat.includes("music")) {
    return "🎵"
  } else if (cat.includes("document")) {
    return "🎥"
  } else {
    return "📺"
  }
}

// Play channel
function playChannel(channel) {
  console.log("[v0] Opening player for:", channel.name)

  const modal = document.getElementById("playerModal")
  const video = document.getElementById("videoPlayer")
  const channelName = document.getElementById("playerChannelName")
  const channelCategory = document.getElementById("playerChannelCategory")

  channelName.textContent = channel.name
  channelCategory.textContent = channel.category

  modal.style.display = "flex"

  setTimeout(() => {
    // Set video source
    video.src = channel.url
    video.load()

    video.addEventListener("loadstart", () => {
      console.log("[v0] Video loading started")
    })

    video.addEventListener("canplay", () => {
      console.log("[v0] Video can play")
    })

    video.addEventListener("error", (e) => {
      console.error("[v0] Video error:", e)
      alert("Erro ao carregar o vídeo. Verifique a URL do stream.")
    })

    console.log("[v0] Playing channel:", channel.name, "URL:", channel.url)
  }, 100)
}

// Close player
function closePlayer() {
  console.log("[v0] Closing player")

  const modal = document.getElementById("playerModal")
  const video = document.getElementById("videoPlayer")

  video.pause()
  video.src = ""
  video.load() // Reset video element

  modal.style.display = "none"
}

// Logout
async function logout() {
  try {
    await fetch("api/logout.php")
    window.location.href = "index.php"
  } catch (error) {
    console.error("[v0] Logout error:", error)
    window.location.href = "index.php"
  }
}

// Show loading
function showLoading() {
  document.getElementById("loadingState").style.display = "flex"
  document.getElementById("errorState").style.display = "none"
  document.querySelectorAll(".content-view").forEach((v) => {
    v.style.display = "none"
  })
}

// Show error
function showError(message) {
  document.getElementById("loadingState").style.display = "none"
  document.getElementById("errorState").style.display = "flex"
  document.getElementById("errorMessage").textContent = message
}

// Escape HTML
function escapeHtml(text) {
  const div = document.createElement("div")
  div.textContent = text
  return div.innerHTML
}

// Close player on ESC key
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") {
    closePlayer()
  }
})
