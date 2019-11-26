# Import this script into your ROS instance (tested for ROS ver >= 6.43)
# Get to the ROS DHCP server config which serves leases for Multigen via RADIUS
# Add the name of this script to "Lease script" section
# Re-create existing leases for your subscribers as ROS runs the script only when lease is created actually

:global leaseBound;
:global leaseActIP;
:local speed "";
:local alreadyExists false;

:global leaseBound;
:global leaseActIP;
:local speed "";
:local alreadyExists false;

:if ($leaseBound = 1) do={
    /queue simple
    :foreach tQueue in=[/queue simple find target="$leaseActIP/32"] do={
            :set speed [get $tQueue max-limit];

            :if ([get $tQueue name] != "mlg_$leaseActIP") do={
                remove $tQueue;
            } else={
                :set alreadyExists true;
            }
    }

    :if (!alreadyExists && $speed != "") do={
        add name="mlg_$leaseActIP" max-limit=$speed target="$leaseActIP/32";
    }
}