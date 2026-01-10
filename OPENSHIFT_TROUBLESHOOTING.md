# OpenShift Troubleshooting Guide

När applikationen körs i OpenShift kan vissa problem uppstå. Här är lösningar för vanliga problem:

## Problem 1: Dialog när man byter folder/katalog

**Orsak**: Session cookies fungerar annorlunda i containeriserade miljöer.

**Lösning**: 
1. Applikationen detekterar automatiskt OpenShift/Kubernetes miljöer
2. Använder mindre strikta cookie-inställningar (`SameSite=Lax` istället för `Strict`)
3. Debugging-endpoints finns tillgängliga:
   - `/view/debug_session.php` - Visar session-information
   - `/view/reset_session.php?reset=1` - Återställer session

## Problem 2: ZIP-nedladdning fungerar inte

**Orsak**: Temp-directory är inte skrivbar i container.

**Lösning**:
1. Applikationen testar flera temp-directories i ordning:
   - `/tmp` (standard i containers)
   - `sys_get_temp_dir()` (PHP default)
   - `../storage` (app-specific)
   - `/var/tmp` (backup)

2. Detaljerad felrapportering visar exakt vad som gick fel

## Debugging

För att debugga problem:

1. **Session-problem**: Besök `/view/debug_session.php`
2. **Reset session**: Besök `/view/reset_session.php?reset=1`
3. **Zip-problem**: Titta på detaljerade felmeddelanden i zip-nedladdning

## Miljövariabler som detekteras

- `OPENSHIFT_BUILD_NAMESPACE` - OpenShift miljö
- `KUBERNETES_SERVICE_HOST` - Kubernetes miljö

När dessa finns använder applikationen container-vänliga inställningar automatiskt.