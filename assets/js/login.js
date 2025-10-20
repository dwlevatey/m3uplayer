document.addEventListener("DOMContentLoaded", async () => {
  console.log("[v0] DOM loaded, initializing login page...")

  // Load DNS servers
  try {
    console.log("[v0] Fetching DNS servers from: api/get-dns-list.php")
    const response = await fetch("api/get-dns-list.php")

    console.log("[v0] Response status:", response.status, response.statusText)

    if (!response.ok) {
      console.error("[v0] HTTP error:", response.status)
      const text = await response.text()
      console.error("[v0] Response text:", text)
    } else {
      const data = await response.json()
      console.log("[v0] DNS list response:", data)

      if (data.success && data.data && data.data.length > 0) {
        const select = document.getElementById("dnsServer")

        if (!select) {
          console.error("[v0] DNS select element not found!")
        } else {
          console.log("[v0] Found select element, adding", data.data.length, "DNS servers")

          data.data.forEach((dns) => {
            const option = document.createElement("option")
            option.value = dns.id
            option.textContent = `${dns.name} (${dns.dns_url})`
            select.appendChild(option)
            console.log("[v0] Added DNS option:", dns.name, "ID:", dns.id)
          })

          console.log("[v0] Successfully loaded", data.data.length, "DNS servers")
        }
      } else {
        console.warn("[v0] No DNS servers found or invalid response:", data)
      }
    }
  } catch (error) {
    console.error("[v0] Error loading DNS servers:", error)
    console.error("[v0] Error details:", error.message, error.stack)
  }

  const loginForm = document.getElementById("loginForm")
  if (!loginForm) {
    console.error("[v0] Login form not found!")
    return
  }

  loginForm.addEventListener("submit", async function (e) {
    e.preventDefault()

    const username = document.getElementById("username").value.trim()
    const password = document.getElementById("password").value.trim()
    const dnsId = document.getElementById("dnsServer").value
    const errorMessage = document.getElementById("errorMessage")
    const submitButton = this.querySelector('button[type="submit"]')

    errorMessage.style.display = "none"

    submitButton.disabled = true
    submitButton.textContent = dnsId ? "Verificando servidor selecionado..." : "Verificando servidores DNS..."

    try {
      const requestBody = { username, password }
      if (dnsId) {
        requestBody.dns_id = Number.parseInt(dnsId)
      }

      console.log("[v0] Sending login request:", { username, dns_id: dnsId || "auto" })

      const response = await fetch("api/client-login.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(requestBody),
      })

      const data = await response.json()

      console.log("[v0] Login response:", data)

      if (data.success) {
        if (data.data.auto_detected) {
          submitButton.textContent = `Encontrado em ${data.data.dns_name}!`
        } else if (data.data.cached) {
          submitButton.textContent = `Conectando ao ${data.data.dns_name}...`
        } else {
          submitButton.textContent = `Conectando ao servidor ${data.data.dns_name}...`
        }

        console.log("[v0] ✅ Login successful!")
        console.log("[v0] DNS:", data.data.dns_name)
        console.log("[v0] URL Base:", data.data.dns_url)
        if (data.data.output_format) {
          console.log("[v0] Formato usado:", data.data.output_format)
        }

        setTimeout(() => {
          window.location.href = "player.php"
        }, 1200)
      } else {
        let errorText = data.message || "Credenciais inválidas"

        if (data.debug) {
          console.error("[v0] ❌ Login failed")

          if (data.debug === "NO_DNS_CONFIGURED") {
            errorText = "⚠️ Nenhum servidor DNS configurado. Configure um servidor DNS no painel admin primeiro."
          } else if (Array.isArray(data.debug)) {
            console.group("🔍 DETALHES DA VERIFICAÇÃO")
            console.log(`Total de servidores testados: ${data.checked_servers || data.debug.length}`)
            console.log(
              `Formatos testados: ${data.formats_tried ? data.formats_tried.join(", ") : "mpegts, m3u8, hls, ts"}`,
            )
            console.log("\n" + "=".repeat(80))

            data.debug.forEach((test, index) => {
              console.log(`\n📡 Servidor ${index + 1}: ${test.dns_name}`)
              console.log(`   URL Base: ${test.dns_base_url}`)
              console.log(`   Formato: ${test.output_format}`)
              console.log(`   URL Completa Testada:`)
              console.log(`   ${test.full_m3u_url}`)
              console.log(`   `)
              console.log(`   Resultado:`)
              console.log(`   - HTTP Code: ${test.http_code}`)
              console.log(
                `   - Resposta recebida: ${test.has_response ? "Sim" : "Não"} ${test.response_length ? `(${test.response_length} bytes)` : ""}`,
              )
              console.log(`   - M3U Válido: ${test.is_valid_m3u ? "✅ SIM" : "❌ NÃO"}`)
              if (test.curl_error) {
                console.log(`   - ⚠️ Erro: ${test.curl_error}`)
              }
              console.log("   " + "-".repeat(76))
            })

            console.log("\n" + "=".repeat(80))
            console.log("\n💡 INSTRUÇÕES:")
            console.log("1. Verifique se a URL base do servidor DNS está correta no painel admin")
            console.log("2. A URL deve ser apenas o domínio, exemplo: http://blder.xyz")
            console.log(
              "3. O sistema adiciona automaticamente: /get.php?username=...&password=...&type=m3u_plus&output=mpegts",
            )
            console.log("\n📋 Exemplo de URL completa que deveria funcionar:")
            console.log("   http://blder.xyz/get.php?username=064397679&password=337776300&type=m3u_plus&output=mpegts")
            console.log("\n🔧 Se você tem uma URL que funciona, cadastre apenas a parte: http://blder.xyz")
            console.groupEnd()
          }
        }

        errorMessage.textContent = errorText
        errorMessage.style.display = "block"
        submitButton.disabled = false
        submitButton.textContent = "Entrar"
      }
    } catch (error) {
      console.error("[v0] Login error:", error)
      errorMessage.textContent = "Erro ao conectar com o servidor. Verifique sua conexão."
      errorMessage.style.display = "block"
      submitButton.disabled = false
      submitButton.textContent = "Entrar"
    }
  })

  console.log("[v0] Login page initialized successfully")
})
