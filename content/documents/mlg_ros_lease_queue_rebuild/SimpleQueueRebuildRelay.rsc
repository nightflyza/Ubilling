# Import this script into your ROS instance (tested for ROS ver >= 6.43)
# Get to the System -> Scheduler
# Add there a job which runs this script, for example, every 30 seconds
# Don't forget to modify this script to avoid changing your own, non-billing simple queue rules

:local qSpeed "";
:local qTarget "";
:local qIP "";

/queue simple
:local tQueueList [find where (name~"^mlg_" = false)];
:local tQueueMLG "";

:log warning ("Not mlg_ queues found: " . [:len $tQueueList]);

:if ([:len $tQueueList] > 0) do={
    :foreach tQueue in=$tQueueList do={
        :set qSpeed [get $tQueue max-limit];
        :set qTarget [get $tQueue target];
        :set qIP [:pick [:tostr $qTarget] 0 [:find [:tostr $qTarget] "/"]];

        remove $tQueue;

        :set tQueueMLG [find where (name="mlg_$qIP")];

		:if ([:len $tQueueMLG] > 0) do={
			:foreach eachQueueMLG in=$tQueueMLG do={
				remove $eachQueueMLG;
			}
		}

        add name="mlg_$qIP" max-limit=$qSpeed target=$qTarget;
    }
}