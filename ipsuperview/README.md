# IP SuperView

`IP SuperView` is a lightweight GLPI plugin for GLPI 10 that turns an IPv4 subnet into:

- a quick occupancy summary
- a duplicate IP report
- a `/24`-style heatmap
- a fixed-IP audit workspace with stale-record highlighting
- a live-scan view for online but unregistered devices
- manual aliases and reserved IP markers

The plugin reads existing GLPI network data from `networkports`, `networknames`, and `ipaddresses`, and can also run an optional live scan for smaller ranges.

## Language Support

- Chinese and English are both supported
- The interface follows the current GLPI session language automatically
- No extra language switch is needed inside the plugin UI

## Scope

- Supported subnet size: `/20` to `/30`
- Heatmap rendering: `/24` or smaller
- Detailed per-IP table: up to 512 usable hosts
- Data source: existing GLPI asset IP assignments
- Live scan: available for subnets up to 1024 usable hosts when `shell_exec` is enabled

## Install

1. Copy the `ipsuperview` directory to your GLPI server `plugins/` directory.
2. In GLPI, open `Setup > Plugins`.
3. Install and enable `IP SuperView`.
4. Grant the `View IP subnet overview` right to the target profile.

## Tested Against

- GLPI `10.0.18`
- Existing reference behavior on `192.168.32.0/24`
- Real deployment validation on a production-style `/24` subnet

## Notes

- The plugin does not change GLPI IP assignments automatically.
- Live-scan results are used for comparison and operator review.
- Export includes summary, fixed-IP mapping, conflicts, unregistered devices, and manual labels.
