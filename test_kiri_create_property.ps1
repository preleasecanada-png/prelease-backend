param(
    [string]$ApiBase = $env:API_BASE_URL ?? "https://your-api-gateway-url.execute-api.us-east-1.amazonaws.com/api"
)

# --- Read auth ---
$token = Get-Content -Raw "$env:TEMP\kiri_test_token.txt"
$userId = Get-Content -Raw "$env:TEMP\kiri_test_uid.txt"
$videoPath = Join-Path $env:TEMP "kiri_test_video.mp4"
$imagePath = Join-Path $env:TEMP "kiri_test_image.jpg"

if (-not (Test-Path $videoPath)) { throw "Video missing: $videoPath" }
if (-not (Test-Path $imagePath)) { throw "Image missing: $imagePath" }

# --- Build multipart body manually (PowerShell 5 friendly) ---
$boundary = [System.Guid]::NewGuid().ToString()
$LF = "`r`n"
$enc = [System.Text.Encoding]::GetEncoding("ISO-8859-1")

function Add-Field($name, $value) {
    return "--$boundary$LF" + `
           "Content-Disposition: form-data; name=`"$name`"$LF$LF" + `
           "$value$LF"
}

function Add-FileField($name, $filePath, $contentType) {
    $bytes = [System.IO.File]::ReadAllBytes($filePath)
    $fileContent = $enc.GetString($bytes)
    $fileName = [System.IO.Path]::GetFileName($filePath)
    return "--$boundary$LF" + `
           "Content-Disposition: form-data; name=`"$name`"; filename=`"$fileName`"$LF" + `
           "Content-Type: $contentType$LF$LF" + `
           "$fileContent$LF"
}

$body  = Add-Field "title"                            "KIRI E2E Test - Modern Loft 3D"
$body += Add-Field "description"                      "Automated end-to-end test of the KIRI Engine 3D virtual tour pipeline. Spacious modern loft in downtown Toronto."
$body += Add-Field "describe_your_place"              "Apartment"
$body += Add-Field "how_many_guests"                  "2"
$body += Add-Field "how_many_bedrooms"                "1"
$body += Add-Field "how_many_bathroom"                "1"
$body += Add-Field "bathroom_avaiable_private_and_attached" "1"
$body += Add-Field "bathroom_avaiable_dedicated"      "0"
$body += Add-Field "bathroom_avaiable_shared"         "0"
$body += Add-Field "who_else_there"                   "Just me"
$body += Add-Field "pets_allowed"                     "0"
$body += Add-Field "set_your_price"                   "1500"
$body += Add-Field "user_id"                          "$userId"
$body += Add-Field "reservation_type"                 "instant"
$body += Add-Field "guest_service_fee"                "0"
$body += Add-Field "discount_1_month"                 "0"
$body += Add-Field "discount_1_month_value"           "0"
$body += Add-Field "discount_3_month"                 "0"
$body += Add-Field "discount_3_month_value"           "0"
$body += Add-Field "discount_6_month"                 "0"
$body += Add-Field "discount_6_month_value"           "0"
$body += Add-Field "country"                          "Canada"
$body += Add-Field "address"                          "100 Front Street West, Toronto, ON M5J 1E3"
$body += Add-Field "street"                           "100 Front Street West"
$body += Add-Field "apt"                              "1201"
$body += Add-Field "city"                             "Toronto"
$body += Add-Field "province"                         "Ontario"
$body += Add-Field "postal"                           "M5J 1E3"
# Amenities: backend expects array of {id} entries (Wi-Fi, TV, AC)
$body += Add-Field "amenities[0][id]" "1"
$body += Add-Field "amenities[1][id]" "2"
$body += Add-Field "amenities[2][id]" "3"
$body += Add-FileField "property_images[]" $imagePath "image/jpeg"
$body += Add-FileField "tour_video"        $videoPath "video/mp4"
$body += "--$boundary--$LF"

$bodyBytes = $enc.GetBytes($body)
Write-Host "Body size: $([math]::Round($bodyBytes.Length / 1MB, 2)) MB"

# --- POST ---
$req = [System.Net.HttpWebRequest]::Create("$ApiBase/property/create")
$req.Method = "POST"
$req.ContentType = "multipart/form-data; boundary=$boundary"
$req.Headers.Add("Authorization", "Bearer $token")
$req.Timeout = 600000
$req.ReadWriteTimeout = 600000
$req.AllowWriteStreamBuffering = $false
$req.ContentLength = $bodyBytes.Length
$reqStream = $req.GetRequestStream()
$reqStream.Write($bodyBytes, 0, $bodyBytes.Length)
$reqStream.Close()

try {
    $resp = $req.GetResponse()
    $reader = New-Object System.IO.StreamReader($resp.GetResponseStream())
    $respBody = $reader.ReadToEnd()
    Write-Host "STATUS: $($resp.StatusCode)"
    Write-Host "RESPONSE:"
    Write-Host $respBody
    $respBody | Out-File -FilePath "$env:TEMP\kiri_test_response.json" -NoNewline
} catch [System.Net.WebException] {
    $errResp = $_.Exception.Response
    if ($errResp) {
        $reader = New-Object System.IO.StreamReader($errResp.GetResponseStream())
        $errBody = $reader.ReadToEnd()
        Write-Host "HTTP ERROR: $($errResp.StatusCode)"
        Write-Host "ERROR BODY: $errBody"
    } else {
        Write-Host "WEB ERROR: $($_.Exception.Message)"
    }
}
