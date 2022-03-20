<?php

class Model {
	public int $id;
	public int $is_new;
	public DateTime $created;
	public DateTime $updated;
	
	function __construct() {
		$this->id = 0;
		$this->is_new = 1;
		$this->created = new DateTime("now", new DateTimeZone("America/Detroit"));
		$this->updated = new DateTime("now", new DateTimeZone("America/Detroit"));
	}
	
	public final function getID(): int { return $this->id; }
	public final function getNewStatus(): int { return $this->is_new; }
	public final function getDateCreated(): DateTime { return $this->created; }
	public final function getDateUpdated(): DateTime { return  $this->updated; }
	public final function getDateCreatedTimestamp(): int { return $this->created->getTimestamp(); }
	public final function getDateUpdatedTimestamp(): int { return  $this->updated->getTimestamp(); }
	public final function getDateCreatedAsIso(): string { return $this->created->format(DateTime::ATOM); }
    public final function getDateUpdatedAsIso(): string { return $this->updated->format(DateTime::ATOM); }
    public final function getDateCreatedFormatted(): string {
		return $this->created->format("M j, Y G:i:s");
	}
    public final function getDateUpdatedFormatted(): string {
        return $this->updated->format("M j, Y G:i:s");
    }
	
	public final function setID(int $p_id): void { $this->id = $p_id; }
	public final function setNewStatus(bool $p_status): void { $this->is_new = (int)$p_status; }
	public final function setDateCreated(string $p_date): void {
		$tmp_date = new DateTime("now", new DateTimeZone("America/Detroit"));
		$tmp_date->setTimestamp(intval($p_date));
		
		$this->created = $tmp_date;
	}
	public final function setDateUpdated(int $p_date): void {
		$tmp_date = new DateTime("now", new DateTimeZone("America/Detroit"));
		$tmp_date->setTimestamp($p_date);
		
		$this->updated = $tmp_date;
	}
	public final function updateTimeToNow(): void {
		$tmp_date = new DateTime("now", new DateTimeZone("America/Detroit"));
		
		$this->setDateUpdated($tmp_date->getTimestamp());
	}

    public final function wasEdited(): bool {
        return $this->created->getTimestamp() !== $this->updated->getTimestamp();
    }
}
