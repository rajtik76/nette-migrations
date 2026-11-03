<?php declare(strict_types=1);

namespace Rajtik\Migrations\Schema;


enum ColumnType
{
	case Id;
	case Integer;
	case BigInteger;
	case ForeignId;
	case String;
	case Text;
	case Boolean;
	case Timestamp;
}
