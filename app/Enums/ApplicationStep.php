<?php

namespace App\Enums;

enum ApplicationStep: string
{
    case GUIDE = 'guide';
    case STUDENT_INFO = 'student-info';
    case STUDENT_TRANSCRIPT = 'student-transcript';
    case MAJORS = 'majors';
    case REVIEW = 'review';
    case SUBMIT = 'submit';
}
